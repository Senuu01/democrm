<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use GuzzleHttp\Client;

class CustomerController extends Controller
{
    private $supabaseUrl;
    private $supabaseKey;
    private $client;

    public function __construct()
    {
        $this->supabaseUrl = config('services.supabase.url');
        $this->supabaseKey = config('services.supabase.key');
        $this->client = new Client();
    }

    /**
     * Display a listing of the resource.
     *
     * @OA\Get(
     *     path="/api/customers",
     *     summary="Get list of customers",
     *     tags={"Customers"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search customers by name, email, or company",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Number of items per page (max 100)",
     *         required=false,
     *         @OA\Schema(type="integer", default=10)
     *     ),
     *     @OA\Parameter(
     *         name="offset",
     *         in="query",
     *         description="Number of items to skip",
     *         required=false,
     *         @OA\Schema(type="integer", default=0)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Customers retrieved successfully"
     *     )
     * )
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            
            // Build query parameters
            $queryParams = [
                'user_id' => 'eq.' . $user['id'],
                'deleted_at' => 'is.null',
                'select' => '*'
            ];

            // Add search functionality
            if ($request->has('search')) {
                $search = $request->input('search');
                $queryParams['or'] = "(name.ilike.*{$search}*,email.ilike.*{$search}*,company.ilike.*{$search}*)";
            }

            // Add status filter
            if ($request->has('status')) {
                $queryParams['status'] = 'eq.' . $request->input('status');
            }

            // Add sorting
            if ($request->has('sort')) {
                $sort = $request->input('sort', 'created_at');
                $order = $request->input('order', 'desc');
                $queryParams['order'] = $sort . '.' . $order;
            } else {
                $queryParams['order'] = 'created_at.desc';
            }

            // Add pagination
            $limit = min($request->input('limit', 10), 100); // Max 100 per page
            $offset = $request->input('offset', 0);
            $queryParams['limit'] = $limit;
            $queryParams['offset'] = $offset;

            $response = $this->client->get($this->supabaseUrl . '/rest/v1/customers', [
                'headers' => [
                    'apikey' => $this->supabaseKey,
                    'Authorization' => 'Bearer ' . $this->supabaseKey,
                    'Content-Type' => 'application/json'
                ],
                'query' => $queryParams
            ]);

            $customers = json_decode($response->getBody()->getContents(), true);

            // Get total count for pagination
            $countParams = [
                'user_id' => 'eq.' . $user['id'],
                'deleted_at' => 'is.null',
                'select' => 'id'
            ];
            
            if ($request->has('search')) {
                $search = $request->input('search');
                $countParams['or'] = "(name.ilike.*{$search}*,email.ilike.*{$search}*,company.ilike.*{$search}*)";
            }
            
            if ($request->has('status')) {
                $countParams['status'] = 'eq.' . $request->input('status');
            }

            $countResponse = $this->client->get($this->supabaseUrl . '/rest/v1/customers', [
                'headers' => [
                    'apikey' => $this->supabaseKey,
                    'Authorization' => 'Bearer ' . $this->supabaseKey,
                    'Content-Type' => 'application/json',
                    'Prefer' => 'count=exact'
                ],
                'query' => $countParams
            ]);

            $totalCount = $countResponse->getHeader('Content-Range')[0] ?? '0';
            $totalCount = explode('/', $totalCount)[1] ?? 0;

            return response()->json([
                'success' => true,
                'message' => 'Customers retrieved successfully',
                'data' => $customers,
                'meta' => [
                    'total' => (int)$totalCount,
                    'limit' => $limit,
                    'offset' => $offset,
                    'has_more' => ($offset + $limit) < $totalCount,
                    'current_page' => floor($offset / $limit) + 1,
                    'total_pages' => ceil($totalCount / $limit)
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('API Customer Index Error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve customers',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @OA\Post(
     *     path="/api/customers",
     *     summary="Create a new customer",
     *     tags={"Customers"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","email"},
     *             @OA\Property(property="name", type="string", maxLength=255),
     *             @OA\Property(property="email", type="string", format="email", maxLength=255),
     *             @OA\Property(property="phone", type="string", maxLength=20),
     *             @OA\Property(property="company", type="string", maxLength=255),
     *             @OA\Property(property="address", type="string", maxLength=500),
     *             @OA\Property(property="status", type="string", enum={"active","inactive","prospect"})
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Customer created successfully"
     *     )
     * )
     */
    public function store(Request $request)
    {
        try {
            $user = $request->user();

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'phone' => 'nullable|string|max:20',
                'company' => 'nullable|string|max:255',
                'address' => 'nullable|string|max:500',
                'status' => 'nullable|in:active,inactive,prospect'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check for duplicate email
            $duplicateResponse = $this->client->get($this->supabaseUrl . '/rest/v1/customers', [
                'headers' => [
                    'apikey' => $this->supabaseKey,
                    'Authorization' => 'Bearer ' . $this->supabaseKey,
                    'Content-Type' => 'application/json'
                ],
                'query' => [
                    'user_id' => 'eq.' . $user['id'],
                    'email' => 'eq.' . $request->input('email'),
                    'deleted_at' => 'is.null',
                    'select' => 'id'
                ]
            ]);

            $duplicates = json_decode($duplicateResponse->getBody()->getContents(), true);
            
            if (!empty($duplicates)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Customer with this email already exists'
                ], 409);
            }

            $customerData = [
                'user_id' => $user['id'],
                'name' => $request->input('name'),
                'email' => $request->input('email'),
                'phone' => $request->input('phone'),
                'company' => $request->input('company'),
                'address' => $request->input('address'),
                'status' => $request->input('status', 'active'),
                'created_at' => now()->toISOString(),
                'updated_at' => now()->toISOString()
            ];

            $response = $this->client->post($this->supabaseUrl . '/rest/v1/customers', [
                'headers' => [
                    'apikey' => $this->supabaseKey,
                    'Authorization' => 'Bearer ' . $this->supabaseKey,
                    'Content-Type' => 'application/json',
                    'Prefer' => 'return=representation'
                ],
                'json' => $customerData
            ]);

            $customer = json_decode($response->getBody()->getContents(), true);

            return response()->json([
                'success' => true,
                'message' => 'Customer created successfully',
                'data' => $customer[0] ?? $customerData
            ], 201);

        } catch (\Exception $e) {
            \Log::error('API Customer Store Error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create customer',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @OA\Get(
     *     path="/api/customers/{id}",
     *     summary="Get a specific customer",
     *     tags={"Customers"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="include",
     *         in="query",
     *         description="Include related data (proposals,invoices)",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Customer retrieved successfully"
     *     )
     * )
     */
    public function show(Request $request, $id)
    {
        try {
            $user = $request->user();

            $response = $this->client->get($this->supabaseUrl . '/rest/v1/customers', [
                'headers' => [
                    'apikey' => $this->supabaseKey,
                    'Authorization' => 'Bearer ' . $this->supabaseKey,
                    'Content-Type' => 'application/json'
                ],
                'query' => [
                    'id' => 'eq.' . $id,
                    'user_id' => 'eq.' . $user['id'],
                    'deleted_at' => 'is.null',
                    'select' => '*'
                ]
            ]);

            $customers = json_decode($response->getBody()->getContents(), true);

            if (empty($customers)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Customer not found'
                ], 404);
            }

            $customer = $customers[0];

            // Include related data if requested
            $include = $request->input('include', '');
            $includeArray = array_filter(explode(',', $include));

            if (in_array('proposals', $includeArray)) {
                $proposalsResponse = $this->client->get($this->supabaseUrl . '/rest/v1/proposals', [
                    'headers' => [
                        'apikey' => $this->supabaseKey,
                        'Authorization' => 'Bearer ' . $this->supabaseKey,
                        'Content-Type' => 'application/json'
                    ],
                    'query' => [
                        'customer_id' => 'eq.' . $id,
                        'select' => 'id,title,amount,status,created_at,updated_at',
                        'order' => 'created_at.desc'
                    ]
                ]);

                $customer['proposals'] = json_decode($proposalsResponse->getBody()->getContents(), true);
            }

            if (in_array('invoices', $includeArray)) {
                $invoicesResponse = $this->client->get($this->supabaseUrl . '/rest/v1/invoices', [
                    'headers' => [
                        'apikey' => $this->supabaseKey,
                        'Authorization' => 'Bearer ' . $this->supabaseKey,
                        'Content-Type' => 'application/json'
                    ],
                    'query' => [
                        'customer_id' => 'eq.' . $id,
                        'select' => 'id,invoice_number,amount,status,due_date,created_at,updated_at',
                        'order' => 'created_at.desc'
                    ]
                ]);

                $customer['invoices'] = json_decode($invoicesResponse->getBody()->getContents(), true);
            }

            return response()->json([
                'success' => true,
                'message' => 'Customer retrieved successfully',
                'data' => $customer
            ]);

        } catch (\Exception $e) {
            \Log::error('API Customer Show Error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve customer',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @OA\Put(
     *     path="/api/customers/{id}",
     *     summary="Update a customer",
     *     tags={"Customers"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", maxLength=255),
     *             @OA\Property(property="email", type="string", format="email", maxLength=255),
     *             @OA\Property(property="phone", type="string", maxLength=20),
     *             @OA\Property(property="company", type="string", maxLength=255),
     *             @OA\Property(property="address", type="string", maxLength=500),
     *             @OA\Property(property="status", type="string", enum={"active","inactive","prospect"})
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Customer updated successfully"
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        try {
            $user = $request->user();

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|max:255',
                'email' => 'sometimes|required|email|max:255',
                'phone' => 'nullable|string|max:20',
                'company' => 'nullable|string|max:255',
                'address' => 'nullable|string|max:500',
                'status' => 'nullable|in:active,inactive,prospect'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check if customer exists and belongs to user
            $checkResponse = $this->client->get($this->supabaseUrl . '/rest/v1/customers', [
                'headers' => [
                    'apikey' => $this->supabaseKey,
                    'Authorization' => 'Bearer ' . $this->supabaseKey,
                    'Content-Type' => 'application/json'
                ],
                'query' => [
                    'id' => 'eq.' . $id,
                    'user_id' => 'eq.' . $user['id'],
                    'deleted_at' => 'is.null',
                    'select' => 'id,email'
                ]
            ]);

            $existingCustomers = json_decode($checkResponse->getBody()->getContents(), true);

            if (empty($existingCustomers)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Customer not found'
                ], 404);
            }

            // Check for duplicate email if email is being updated
            if ($request->has('email') && $request->input('email') !== $existingCustomers[0]['email']) {
                $duplicateResponse = $this->client->get($this->supabaseUrl . '/rest/v1/customers', [
                    'headers' => [
                        'apikey' => $this->supabaseKey,
                        'Authorization' => 'Bearer ' . $this->supabaseKey,
                        'Content-Type' => 'application/json'
                    ],
                    'query' => [
                        'user_id' => 'eq.' . $user['id'],
                        'email' => 'eq.' . $request->input('email'),
                        'deleted_at' => 'is.null',
                        'id' => 'neq.' . $id,
                        'select' => 'id'
                    ]
                ]);

                $duplicates = json_decode($duplicateResponse->getBody()->getContents(), true);
                
                if (!empty($duplicates)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Customer with this email already exists'
                    ], 409);
                }
            }

            $updateData = array_filter([
                'name' => $request->input('name'),
                'email' => $request->input('email'),
                'phone' => $request->input('phone'),
                'company' => $request->input('company'),
                'address' => $request->input('address'),
                'status' => $request->input('status'),
                'updated_at' => now()->toISOString()
            ], function($value) {
                return $value !== null && $value !== '';
            });

            $response = $this->client->patch($this->supabaseUrl . '/rest/v1/customers', [
                'headers' => [
                    'apikey' => $this->supabaseKey,
                    'Authorization' => 'Bearer ' . $this->supabaseKey,
                    'Content-Type' => 'application/json',
                    'Prefer' => 'return=representation'
                ],
                'query' => [
                    'id' => 'eq.' . $id,
                    'user_id' => 'eq.' . $user['id']
                ],
                'json' => $updateData
            ]);

            $customer = json_decode($response->getBody()->getContents(), true);

            return response()->json([
                'success' => true,
                'message' => 'Customer updated successfully',
                'data' => $customer[0] ?? null
            ]);

        } catch (\Exception $e) {
            \Log::error('API Customer Update Error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update customer',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @OA\Delete(
     *     path="/api/customers/{id}",
     *     summary="Delete a customer",
     *     tags={"Customers"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Customer deleted successfully"
     *     )
     * )
     */
    public function destroy(Request $request, $id)
    {
        try {
            $user = $request->user();

            // Check if customer exists and belongs to user
            $checkResponse = $this->client->get($this->supabaseUrl . '/rest/v1/customers', [
                'headers' => [
                    'apikey' => $this->supabaseKey,
                    'Authorization' => 'Bearer ' . $this->supabaseKey,
                    'Content-Type' => 'application/json'
                ],
                'query' => [
                    'id' => 'eq.' . $id,
                    'user_id' => 'eq.' . $user['id'],
                    'deleted_at' => 'is.null',
                    'select' => 'id,name'
                ]
            ]);

            $existingCustomers = json_decode($checkResponse->getBody()->getContents(), true);

            if (empty($existingCustomers)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Customer not found'
                ], 404);
            }

            // Soft delete by setting deleted_at
            $response = $this->client->patch($this->supabaseUrl . '/rest/v1/customers', [
                'headers' => [
                    'apikey' => $this->supabaseKey,
                    'Authorization' => 'Bearer ' . $this->supabaseKey,
                    'Content-Type' => 'application/json'
                ],
                'query' => [
                    'id' => 'eq.' . $id,
                    'user_id' => 'eq.' . $user['id']
                ],
                'json' => [
                    'deleted_at' => now()->toISOString(),
                    'updated_at' => now()->toISOString()
                ]
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Customer deleted successfully',
                'data' => [
                    'id' => (int)$id,
                    'deleted_at' => now()->toISOString()
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('API Customer Delete Error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete customer',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get customer statistics
     *
     * @OA\Get(
     *     path="/api/customers/stats",
     *     summary="Get customer statistics",
     *     tags={"Customers"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Customer statistics retrieved successfully"
     *     )
     * )
     */
    public function stats(Request $request)
    {
        try {
            $user = $request->user();

            // Get total customers
            $customersResponse = $this->client->get($this->supabaseUrl . '/rest/v1/customers', [
                'headers' => [
                    'apikey' => $this->supabaseKey,
                    'Authorization' => 'Bearer ' . $this->supabaseKey,
                    'Content-Type' => 'application/json',
                    'Prefer' => 'count=exact'
                ],
                'query' => [
                    'user_id' => 'eq.' . $user['id'],
                    'deleted_at' => 'is.null',
                    'select' => 'id'
                ]
            ]);

            $totalCustomers = $customersResponse->getHeader('Content-Range')[0] ?? '0';
            $totalCustomers = explode('/', $totalCustomers)[1] ?? 0;

            // Get active customers
            $activeCustomersResponse = $this->client->get($this->supabaseUrl . '/rest/v1/customers', [
                'headers' => [
                    'apikey' => $this->supabaseKey,
                    'Authorization' => 'Bearer ' . $this->supabaseKey,
                    'Content-Type' => 'application/json',
                    'Prefer' => 'count=exact'
                ],
                'query' => [
                    'user_id' => 'eq.' . $user['id'],
                    'status' => 'eq.active',
                    'deleted_at' => 'is.null',
                    'select' => 'id'
                ]
            ]);

            $activeCustomers = $activeCustomersResponse->getHeader('Content-Range')[0] ?? '0';
            $activeCustomers = explode('/', $activeCustomers)[1] ?? 0;

            // Get new customers this month
            $thisMonth = now()->startOfMonth()->toISOString();
            $newCustomersResponse = $this->client->get($this->supabaseUrl . '/rest/v1/customers', [
                'headers' => [
                    'apikey' => $this->supabaseKey,
                    'Authorization' => 'Bearer ' . $this->supabaseKey,
                    'Content-Type' => 'application/json',
                    'Prefer' => 'count=exact'
                ],
                'query' => [
                    'user_id' => 'eq.' . $user['id'],
                    'created_at' => 'gte.' . $thisMonth,
                    'deleted_at' => 'is.null',
                    'select' => 'id'
                ]
            ]);

            $newCustomers = $newCustomersResponse->getHeader('Content-Range')[0] ?? '0';
            $newCustomers = explode('/', $newCustomers)[1] ?? 0;

            // Get prospect customers
            $prospectCustomersResponse = $this->client->get($this->supabaseUrl . '/rest/v1/customers', [
                'headers' => [
                    'apikey' => $this->supabaseKey,
                    'Authorization' => 'Bearer ' . $this->supabaseKey,
                    'Content-Type' => 'application/json',
                    'Prefer' => 'count=exact'
                ],
                'query' => [
                    'user_id' => 'eq.' . $user['id'],
                    'status' => 'eq.prospect',
                    'deleted_at' => 'is.null',
                    'select' => 'id'
                ]
            ]);

            $prospectCustomers = $prospectCustomersResponse->getHeader('Content-Range')[0] ?? '0';
            $prospectCustomers = explode('/', $prospectCustomers)[1] ?? 0;

            return response()->json([
                'success' => true,
                'message' => 'Customer statistics retrieved successfully',
                'data' => [
                    'total_customers' => (int)$totalCustomers,
                    'active_customers' => (int)$activeCustomers,
                    'inactive_customers' => (int)$totalCustomers - (int)$activeCustomers - (int)$prospectCustomers,
                    'prospect_customers' => (int)$prospectCustomers,
                    'new_this_month' => (int)$newCustomers,
                    'generated_at' => now()->toISOString()
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('API Customer Stats Error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve customer statistics',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Bulk operations on customers
     *
     * @OA\Post(
     *     path="/api/customers/bulk",
     *     summary="Perform bulk operations on customers",
     *     tags={"Customers"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"action","customer_ids"},
     *             @OA\Property(property="action", type="string", enum={"delete","update_status"}),
     *             @OA\Property(property="customer_ids", type="array", @OA\Items(type="integer")),
     *             @OA\Property(property="status", type="string", enum={"active","inactive","prospect"})
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Bulk operation completed successfully"
     *     )
     * )
     */
    public function bulk(Request $request)
    {
        try {
            $user = $request->user();

            $validator = Validator::make($request->all(), [
                'action' => 'required|in:delete,update_status',
                'customer_ids' => 'required|array|min:1',
                'customer_ids.*' => 'required|integer',
                'status' => 'required_if:action,update_status|in:active,inactive,prospect'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $action = $request->input('action');
            $customerIds = $request->input('customer_ids');
            $status = $request->input('status');

            $results = [];
            $errors = [];

            foreach ($customerIds as $customerId) {
                try {
                    // Verify customer belongs to user
                    $checkResponse = $this->client->get($this->supabaseUrl . '/rest/v1/customers', [
                        'headers' => [
                            'apikey' => $this->supabaseKey,
                            'Authorization' => 'Bearer ' . $this->supabaseKey,
                            'Content-Type' => 'application/json'
                        ],
                        'query' => [
                            'id' => 'eq.' . $customerId,
                            'user_id' => 'eq.' . $user['id'],
                            'deleted_at' => 'is.null',
                            'select' => 'id'
                        ]
                    ]);

                    $existingCustomers = json_decode($checkResponse->getBody()->getContents(), true);

                    if (empty($existingCustomers)) {
                        $errors[] = "Customer {$customerId} not found";
                        continue;
                    }

                    if ($action === 'delete') {
                        $updateData = [
                            'deleted_at' => now()->toISOString(),
                            'updated_at' => now()->toISOString()
                        ];
                    } else { // update_status
                        $updateData = [
                            'status' => $status,
                            'updated_at' => now()->toISOString()
                        ];
                    }

                    $this->client->patch($this->supabaseUrl . '/rest/v1/customers', [
                        'headers' => [
                            'apikey' => $this->supabaseKey,
                            'Authorization' => 'Bearer ' . $this->supabaseKey,
                            'Content-Type' => 'application/json'
                        ],
                        'query' => [
                            'id' => 'eq.' . $customerId,
                            'user_id' => 'eq.' . $user['id']
                        ],
                        'json' => $updateData
                    ]);

                    $results[] = $customerId;

                } catch (\Exception $e) {
                    $errors[] = "Failed to process customer {$customerId}: " . $e->getMessage();
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Bulk {$action} completed",
                'data' => [
                    'processed_customers' => $results,
                    'total_processed' => count($results),
                    'total_requested' => count($customerIds),
                    'errors' => $errors
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('API Customer Bulk Error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Bulk operation failed',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
}
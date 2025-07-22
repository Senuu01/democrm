<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use GuzzleHttp\Client;

class ProposalController extends Controller
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
     * Display a listing of proposals.
     *
     * @OA\Get(
     *     path="/api/proposals",
     *     summary="Get list of proposals",
     *     tags={"Proposals"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search proposals by title or customer name",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by status",
     *         required=false,
     *         @OA\Schema(type="string", enum={"draft","sent","accepted","rejected"})
     *     ),
     *     @OA\Parameter(
     *         name="customer_id",
     *         in="query",
     *         description="Filter by customer ID",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Proposals retrieved successfully"
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
                'select' => '*, customer:customer_id(id,name,email,company)'
            ];

            // Add search functionality
            if ($request->has('search')) {
                $search = $request->input('search');
                $queryParams['or'] = "(title.ilike.*{$search}*,description.ilike.*{$search}*)";
            }

            // Add status filter
            if ($request->has('status')) {
                $queryParams['status'] = 'eq.' . $request->input('status');
            }

            // Add customer filter
            if ($request->has('customer_id')) {
                $queryParams['customer_id'] = 'eq.' . $request->input('customer_id');
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
            $limit = min($request->input('limit', 10), 100);
            $offset = $request->input('offset', 0);
            $queryParams['limit'] = $limit;
            $queryParams['offset'] = $offset;

            $response = $this->client->get($this->supabaseUrl . '/rest/v1/proposals', [
                'headers' => [
                    'apikey' => $this->supabaseKey,
                    'Authorization' => 'Bearer ' . $this->supabaseKey,
                    'Content-Type' => 'application/json'
                ],
                'query' => $queryParams
            ]);

            $proposals = json_decode($response->getBody()->getContents(), true);

            // Get total count
            $countParams = [
                'user_id' => 'eq.' . $user['id'],
                'select' => 'id'
            ];
            
            if ($request->has('search')) {
                $search = $request->input('search');
                $countParams['or'] = "(title.ilike.*{$search}*,description.ilike.*{$search}*)";
            }
            
            if ($request->has('status')) {
                $countParams['status'] = 'eq.' . $request->input('status');
            }

            if ($request->has('customer_id')) {
                $countParams['customer_id'] = 'eq.' . $request->input('customer_id');
            }

            $countResponse = $this->client->get($this->supabaseUrl . '/rest/v1/proposals', [
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
                'message' => 'Proposals retrieved successfully',
                'data' => $proposals,
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
            \Log::error('API Proposal Index Error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve proposals',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Store a newly created proposal.
     *
     * @OA\Post(
     *     path="/api/proposals",
     *     summary="Create a new proposal",
     *     tags={"Proposals"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"title","customer_id","line_items"},
     *             @OA\Property(property="title", type="string", maxLength=255),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="customer_id", type="integer"),
     *             @OA\Property(property="terms_conditions", type="string"),
     *             @OA\Property(property="line_items", type="array", @OA\Items(
     *                 @OA\Property(property="description", type="string"),
     *                 @OA\Property(property="quantity", type="number"),
     *                 @OA\Property(property="price", type="number")
     *             ))
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Proposal created successfully"
     *     )
     * )
     */
    public function store(Request $request)
    {
        try {
            $user = $request->user();

            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'customer_id' => 'required|integer',
                'terms_conditions' => 'nullable|string',
                'line_items' => 'required|array|min:1',
                'line_items.*.description' => 'required|string|max:255',
                'line_items.*.quantity' => 'required|numeric|min:0.01',
                'line_items.*.price' => 'required|numeric|min:0'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Verify customer belongs to user
            $customerResponse = $this->client->get($this->supabaseUrl . '/rest/v1/customers', [
                'headers' => [
                    'apikey' => $this->supabaseKey,
                    'Authorization' => 'Bearer ' . $this->supabaseKey,
                    'Content-Type' => 'application/json'
                ],
                'query' => [
                    'id' => 'eq.' . $request->input('customer_id'),
                    'user_id' => 'eq.' . $user['id'],
                    'deleted_at' => 'is.null',
                    'select' => 'id,name,email'
                ]
            ]);

            $customers = json_decode($customerResponse->getBody()->getContents(), true);
            
            if (empty($customers)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Customer not found or does not belong to you'
                ], 404);
            }

            // Calculate total amount
            $lineItems = $request->input('line_items');
            $totalAmount = 0;
            foreach ($lineItems as $item) {
                $totalAmount += $item['quantity'] * $item['price'];
            }

            // Generate proposal number
            $proposalNumber = 'PROP-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

            $proposalData = [
                'user_id' => $user['id'],
                'customer_id' => $request->input('customer_id'),
                'proposal_number' => $proposalNumber,
                'title' => $request->input('title'),
                'description' => $request->input('description'),
                'terms_conditions' => $request->input('terms_conditions'),
                'line_items' => json_encode($lineItems),
                'amount' => $totalAmount,
                'status' => 'draft',
                'created_at' => now()->toISOString(),
                'updated_at' => now()->toISOString()
            ];

            $response = $this->client->post($this->supabaseUrl . '/rest/v1/proposals', [
                'headers' => [
                    'apikey' => $this->supabaseKey,
                    'Authorization' => 'Bearer ' . $this->supabaseKey,
                    'Content-Type' => 'application/json',
                    'Prefer' => 'return=representation'
                ],
                'json' => $proposalData
            ]);

            $proposal = json_decode($response->getBody()->getContents(), true);

            return response()->json([
                'success' => true,
                'message' => 'Proposal created successfully',
                'data' => $proposal[0] ?? $proposalData
            ], 201);

        } catch (\Exception $e) {
            \Log::error('API Proposal Store Error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create proposal',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Display the specified proposal.
     *
     * @OA\Get(
     *     path="/api/proposals/{id}",
     *     summary="Get a specific proposal",
     *     tags={"Proposals"},
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
     *         description="Include related data (customer)",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Proposal retrieved successfully"
     *     )
     * )
     */
    public function show(Request $request, $id)
    {
        try {
            $user = $request->user();

            $selectFields = '*';
            $include = $request->input('include', '');
            $includeArray = array_filter(explode(',', $include));

            if (in_array('customer', $includeArray)) {
                $selectFields .= ', customer:customer_id(id,name,email,company,phone,address)';
            }

            $response = $this->client->get($this->supabaseUrl . '/rest/v1/proposals', [
                'headers' => [
                    'apikey' => $this->supabaseKey,
                    'Authorization' => 'Bearer ' . $this->supabaseKey,
                    'Content-Type' => 'application/json'
                ],
                'query' => [
                    'id' => 'eq.' . $id,
                    'user_id' => 'eq.' . $user['id'],
                    'select' => $selectFields
                ]
            ]);

            $proposals = json_decode($response->getBody()->getContents(), true);

            if (empty($proposals)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Proposal not found'
                ], 404);
            }

            $proposal = $proposals[0];

            // Parse line items JSON
            if (isset($proposal['line_items']) && is_string($proposal['line_items'])) {
                $proposal['line_items'] = json_decode($proposal['line_items'], true) ?? [];
            }

            return response()->json([
                'success' => true,
                'message' => 'Proposal retrieved successfully',
                'data' => $proposal
            ]);

        } catch (\Exception $e) {
            \Log::error('API Proposal Show Error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve proposal',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Update the specified proposal.
     *
     * @OA\Put(
     *     path="/api/proposals/{id}",
     *     summary="Update a proposal",
     *     tags={"Proposals"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Proposal updated successfully"
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        try {
            $user = $request->user();

            $validator = Validator::make($request->all(), [
                'title' => 'sometimes|required|string|max:255',
                'description' => 'nullable|string',
                'customer_id' => 'sometimes|required|integer',
                'terms_conditions' => 'nullable|string',
                'line_items' => 'sometimes|required|array|min:1',
                'line_items.*.description' => 'required|string|max:255',
                'line_items.*.quantity' => 'required|numeric|min:0.01',
                'line_items.*.price' => 'required|numeric|min:0',
                'status' => 'sometimes|in:draft,sent,accepted,rejected'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check if proposal exists and belongs to user
            $checkResponse = $this->client->get($this->supabaseUrl . '/rest/v1/proposals', [
                'headers' => [
                    'apikey' => $this->supabaseKey,
                    'Authorization' => 'Bearer ' . $this->supabaseKey,
                    'Content-Type' => 'application/json'
                ],
                'query' => [
                    'id' => 'eq.' . $id,
                    'user_id' => 'eq.' . $user['id'],
                    'select' => 'id,status'
                ]
            ]);

            $existingProposals = json_decode($checkResponse->getBody()->getContents(), true);

            if (empty($existingProposals)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Proposal not found'
                ], 404);
            }

            $existingProposal = $existingProposals[0];

            // Check if proposal can be edited (not accepted/rejected)
            if (in_array($existingProposal['status'], ['accepted', 'rejected']) && !$request->has('status')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot edit accepted or rejected proposals'
                ], 422);
            }

            $updateData = [];

            if ($request->has('title')) {
                $updateData['title'] = $request->input('title');
            }

            if ($request->has('description')) {
                $updateData['description'] = $request->input('description');
            }

            if ($request->has('terms_conditions')) {
                $updateData['terms_conditions'] = $request->input('terms_conditions');
            }

            if ($request->has('status')) {
                $updateData['status'] = $request->input('status');
            }

            if ($request->has('customer_id')) {
                // Verify customer belongs to user
                $customerResponse = $this->client->get($this->supabaseUrl . '/rest/v1/customers', [
                    'headers' => [
                        'apikey' => $this->supabaseKey,
                        'Authorization' => 'Bearer ' . $this->supabaseKey,
                        'Content-Type' => 'application/json'
                    ],
                    'query' => [
                        'id' => 'eq.' . $request->input('customer_id'),
                        'user_id' => 'eq.' . $user['id'],
                        'deleted_at' => 'is.null',
                        'select' => 'id'
                    ]
                ]);

                $customers = json_decode($customerResponse->getBody()->getContents(), true);
                
                if (empty($customers)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Customer not found or does not belong to you'
                    ], 404);
                }

                $updateData['customer_id'] = $request->input('customer_id');
            }

            if ($request->has('line_items')) {
                $lineItems = $request->input('line_items');
                $totalAmount = 0;
                foreach ($lineItems as $item) {
                    $totalAmount += $item['quantity'] * $item['price'];
                }
                
                $updateData['line_items'] = json_encode($lineItems);
                $updateData['amount'] = $totalAmount;
            }

            $updateData['updated_at'] = now()->toISOString();

            $response = $this->client->patch($this->supabaseUrl . '/rest/v1/proposals', [
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

            $proposal = json_decode($response->getBody()->getContents(), true);

            return response()->json([
                'success' => true,
                'message' => 'Proposal updated successfully',
                'data' => $proposal[0] ?? null
            ]);

        } catch (\Exception $e) {
            \Log::error('API Proposal Update Error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update proposal',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Remove the specified proposal.
     *
     * @OA\Delete(
     *     path="/api/proposals/{id}",
     *     summary="Delete a proposal",
     *     tags={"Proposals"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Proposal deleted successfully"
     *     )
     * )
     */
    public function destroy(Request $request, $id)
    {
        try {
            $user = $request->user();

            // Check if proposal exists and belongs to user
            $checkResponse = $this->client->get($this->supabaseUrl . '/rest/v1/proposals', [
                'headers' => [
                    'apikey' => $this->supabaseKey,
                    'Authorization' => 'Bearer ' . $this->supabaseKey,
                    'Content-Type' => 'application/json'
                ],
                'query' => [
                    'id' => 'eq.' . $id,
                    'user_id' => 'eq.' . $user['id'],
                    'select' => 'id,status'
                ]
            ]);

            $existingProposals = json_decode($checkResponse->getBody()->getContents(), true);

            if (empty($existingProposals)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Proposal not found'
                ], 404);
            }

            $existingProposal = $existingProposals[0];

            // Check if proposal can be deleted (only draft proposals)
            if ($existingProposal['status'] !== 'draft') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only draft proposals can be deleted'
                ], 422);
            }

            // Delete the proposal
            $response = $this->client->delete($this->supabaseUrl . '/rest/v1/proposals', [
                'headers' => [
                    'apikey' => $this->supabaseKey,
                    'Authorization' => 'Bearer ' . $this->supabaseKey,
                    'Content-Type' => 'application/json'
                ],
                'query' => [
                    'id' => 'eq.' . $id,
                    'user_id' => 'eq.' . $user['id']
                ]
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Proposal deleted successfully',
                'data' => [
                    'id' => (int)$id,
                    'deleted_at' => now()->toISOString()
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('API Proposal Delete Error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete proposal',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get proposal statistics
     *
     * @OA\Get(
     *     path="/api/proposals/stats",
     *     summary="Get proposal statistics",
     *     tags={"Proposals"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Proposal statistics retrieved successfully"
     *     )
     * )
     */
    public function stats(Request $request)
    {
        try {
            $user = $request->user();

            // Get total proposals
            $totalResponse = $this->client->get($this->supabaseUrl . '/rest/v1/proposals', [
                'headers' => [
                    'apikey' => $this->supabaseKey,
                    'Authorization' => 'Bearer ' . $this->supabaseKey,
                    'Content-Type' => 'application/json',
                    'Prefer' => 'count=exact'
                ],
                'query' => [
                    'user_id' => 'eq.' . $user['id'],
                    'select' => 'id'
                ]
            ]);

            $totalProposals = $totalResponse->getHeader('Content-Range')[0] ?? '0';
            $totalProposals = explode('/', $totalProposals)[1] ?? 0;

            // Get proposals by status
            $statuses = ['draft', 'sent', 'accepted', 'rejected'];
            $statusCounts = [];
            $totalValue = 0;
            $acceptedValue = 0;

            foreach ($statuses as $status) {
                $statusResponse = $this->client->get($this->supabaseUrl . '/rest/v1/proposals', [
                    'headers' => [
                        'apikey' => $this->supabaseKey,
                        'Authorization' => 'Bearer ' . $this->supabaseKey,
                        'Content-Type' => 'application/json',
                        'Prefer' => 'count=exact'
                    ],
                    'query' => [
                        'user_id' => 'eq.' . $user['id'],
                        'status' => 'eq.' . $status,
                        'select' => 'id'
                    ]
                ]);

                $count = $statusResponse->getHeader('Content-Range')[0] ?? '0';
                $statusCounts[$status] = (int)explode('/', $count)[1] ?? 0;
            }

            // Get total and accepted values
            $valueResponse = $this->client->get($this->supabaseUrl . '/rest/v1/proposals', [
                'headers' => [
                    'apikey' => $this->supabaseKey,
                    'Authorization' => 'Bearer ' . $this->supabaseKey,
                    'Content-Type' => 'application/json'
                ],
                'query' => [
                    'user_id' => 'eq.' . $user['id'],
                    'select' => 'amount,status'
                ]
            ]);

            $proposals = json_decode($valueResponse->getBody()->getContents(), true);
            
            foreach ($proposals as $proposal) {
                $amount = floatval($proposal['amount'] ?? 0);
                $totalValue += $amount;
                if ($proposal['status'] === 'accepted') {
                    $acceptedValue += $amount;
                }
            }

            $conversionRate = $totalProposals > 0 ? ($statusCounts['accepted'] / $totalProposals) * 100 : 0;

            return response()->json([
                'success' => true,
                'message' => 'Proposal statistics retrieved successfully',
                'data' => [
                    'total_proposals' => (int)$totalProposals,
                    'draft_proposals' => $statusCounts['draft'],
                    'sent_proposals' => $statusCounts['sent'],
                    'accepted_proposals' => $statusCounts['accepted'],
                    'rejected_proposals' => $statusCounts['rejected'],
                    'total_value' => $totalValue,
                    'accepted_value' => $acceptedValue,
                    'conversion_rate' => round($conversionRate, 2),
                    'average_value' => $totalProposals > 0 ? round($totalValue / $totalProposals, 2) : 0,
                    'generated_at' => now()->toISOString()
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('API Proposal Stats Error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve proposal statistics',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Update proposal status
     *
     * @OA\Patch(
     *     path="/api/proposals/{id}/status",
     *     summary="Update proposal status",
     *     tags={"Proposals"},
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
     *             required={"status"},
     *             @OA\Property(property="status", type="string", enum={"draft","sent","accepted","rejected"})
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Proposal status updated successfully"
     *     )
     * )
     */
    public function updateStatus(Request $request, $id)
    {
        try {
            $user = $request->user();

            $validator = Validator::make($request->all(), [
                'status' => 'required|in:draft,sent,accepted,rejected'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check if proposal exists and belongs to user
            $checkResponse = $this->client->get($this->supabaseUrl . '/rest/v1/proposals', [
                'headers' => [
                    'apikey' => $this->supabaseKey,
                    'Authorization' => 'Bearer ' . $this->supabaseKey,
                    'Content-Type' => 'application/json'
                ],
                'query' => [
                    'id' => 'eq.' . $id,
                    'user_id' => 'eq.' . $user['id'],
                    'select' => 'id,status'
                ]
            ]);

            $existingProposals = json_decode($checkResponse->getBody()->getContents(), true);

            if (empty($existingProposals)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Proposal not found'
                ], 404);
            }

            $newStatus = $request->input('status');
            $updateData = [
                'status' => $newStatus,
                'updated_at' => now()->toISOString()
            ];

            // Add timestamp for status changes
            switch ($newStatus) {
                case 'sent':
                    $updateData['sent_at'] = now()->toISOString();
                    break;
                case 'accepted':
                    $updateData['accepted_at'] = now()->toISOString();
                    break;
                case 'rejected':
                    $updateData['rejected_at'] = now()->toISOString();
                    break;
            }

            $response = $this->client->patch($this->supabaseUrl . '/rest/v1/proposals', [
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

            $proposal = json_decode($response->getBody()->getContents(), true);

            return response()->json([
                'success' => true,
                'message' => "Proposal status updated to {$newStatus}",
                'data' => $proposal[0] ?? null
            ]);

        } catch (\Exception $e) {
            \Log::error('API Proposal Status Update Error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update proposal status',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
} 
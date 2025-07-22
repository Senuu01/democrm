<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use GuzzleHttp\Client;

class InvoiceController extends Controller
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
     * Display a listing of invoices.
     *
     * @OA\Get(
     *     path="/api/invoices",
     *     summary="Get list of invoices",
     *     tags={"Invoices"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search invoices by number or customer name",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filter by status",
     *         required=false,
     *         @OA\Schema(type="string", enum={"draft","sent","paid","overdue","cancelled"})
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
     *         description="Invoices retrieved successfully"
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
                $queryParams['or'] = "(invoice_number.ilike.*{$search}*,description.ilike.*{$search}*)";
            }

            // Add status filter
            if ($request->has('status')) {
                $queryParams['status'] = 'eq.' . $request->input('status');
            }

            // Add customer filter
            if ($request->has('customer_id')) {
                $queryParams['customer_id'] = 'eq.' . $request->input('customer_id');
            }

            // Add date range filter
            if ($request->has('date_from')) {
                $queryParams['created_at'] = 'gte.' . $request->input('date_from');
            }
            
            if ($request->has('date_to')) {
                $queryParams['created_at'] = 'lte.' . $request->input('date_to');
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

            $response = $this->client->get($this->supabaseUrl . '/rest/v1/invoices', [
                'headers' => [
                    'apikey' => $this->supabaseKey,
                    'Authorization' => 'Bearer ' . $this->supabaseKey,
                    'Content-Type' => 'application/json'
                ],
                'query' => $queryParams
            ]);

            $invoices = json_decode($response->getBody()->getContents(), true);

            // Get total count
            $countParams = [
                'user_id' => 'eq.' . $user['id'],
                'select' => 'id'
            ];
            
            if ($request->has('search')) {
                $search = $request->input('search');
                $countParams['or'] = "(invoice_number.ilike.*{$search}*,description.ilike.*{$search}*)";
            }
            
            if ($request->has('status')) {
                $countParams['status'] = 'eq.' . $request->input('status');
            }

            if ($request->has('customer_id')) {
                $countParams['customer_id'] = 'eq.' . $request->input('customer_id');
            }

            $countResponse = $this->client->get($this->supabaseUrl . '/rest/v1/invoices', [
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

            // Calculate overdue invoices automatically
            $this->updateOverdueInvoices($user['id']);

            return response()->json([
                'success' => true,
                'message' => 'Invoices retrieved successfully',
                'data' => $invoices,
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
            \Log::error('API Invoice Index Error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve invoices',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Store a newly created invoice.
     *
     * @OA\Post(
     *     path="/api/invoices",
     *     summary="Create a new invoice",
     *     tags={"Invoices"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"customer_id","line_items","due_date"},
     *             @OA\Property(property="customer_id", type="integer"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="due_date", type="string", format="date"),
     *             @OA\Property(property="line_items", type="array", @OA\Items(
     *                 @OA\Property(property="description", type="string"),
     *                 @OA\Property(property="quantity", type="number"),
     *                 @OA\Property(property="price", type="number")
     *             ))
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Invoice created successfully"
     *     )
     * )
     */
    public function store(Request $request)
    {
        try {
            $user = $request->user();

            $validator = Validator::make($request->all(), [
                'customer_id' => 'required|integer',
                'description' => 'nullable|string',
                'due_date' => 'required|date|after:today',
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

            // Generate invoice number
            $invoiceNumber = 'INV-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

            $invoiceData = [
                'user_id' => $user['id'],
                'customer_id' => $request->input('customer_id'),
                'invoice_number' => $invoiceNumber,
                'description' => $request->input('description'),
                'line_items' => json_encode($lineItems),
                'amount' => $totalAmount,
                'due_date' => $request->input('due_date'),
                'status' => 'draft',
                'created_at' => now()->toISOString(),
                'updated_at' => now()->toISOString()
            ];

            $response = $this->client->post($this->supabaseUrl . '/rest/v1/invoices', [
                'headers' => [
                    'apikey' => $this->supabaseKey,
                    'Authorization' => 'Bearer ' . $this->supabaseKey,
                    'Content-Type' => 'application/json',
                    'Prefer' => 'return=representation'
                ],
                'json' => $invoiceData
            ]);

            $invoice = json_decode($response->getBody()->getContents(), true);

            return response()->json([
                'success' => true,
                'message' => 'Invoice created successfully',
                'data' => $invoice[0] ?? $invoiceData
            ], 201);

        } catch (\Exception $e) {
            \Log::error('API Invoice Store Error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create invoice',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Display the specified invoice.
     *
     * @OA\Get(
     *     path="/api/invoices/{id}",
     *     summary="Get a specific invoice",
     *     tags={"Invoices"},
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
     *         description="Include related data (customer,transactions)",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Invoice retrieved successfully"
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

            $response = $this->client->get($this->supabaseUrl . '/rest/v1/invoices', [
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

            $invoices = json_decode($response->getBody()->getContents(), true);

            if (empty($invoices)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invoice not found'
                ], 404);
            }

            $invoice = $invoices[0];

            // Parse line items JSON
            if (isset($invoice['line_items']) && is_string($invoice['line_items'])) {
                $invoice['line_items'] = json_decode($invoice['line_items'], true) ?? [];
            }

            // Include transactions if requested
            if (in_array('transactions', $includeArray)) {
                $transactionsResponse = $this->client->get($this->supabaseUrl . '/rest/v1/transactions', [
                    'headers' => [
                        'apikey' => $this->supabaseKey,
                        'Authorization' => 'Bearer ' . $this->supabaseKey,
                        'Content-Type' => 'application/json'
                    ],
                    'query' => [
                        'invoice_id' => 'eq.' . $id,
                        'select' => 'id,amount,status,payment_method,created_at,updated_at',
                        'order' => 'created_at.desc'
                    ]
                ]);

                $invoice['transactions'] = json_decode($transactionsResponse->getBody()->getContents(), true);
            }

            return response()->json([
                'success' => true,
                'message' => 'Invoice retrieved successfully',
                'data' => $invoice
            ]);

        } catch (\Exception $e) {
            \Log::error('API Invoice Show Error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve invoice',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Update the specified invoice.
     *
     * @OA\Put(
     *     path="/api/invoices/{id}",
     *     summary="Update an invoice",
     *     tags={"Invoices"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Invoice updated successfully"
     *     )
     * )
     */
    public function update(Request $request, $id)
    {
        try {
            $user = $request->user();

            $validator = Validator::make($request->all(), [
                'customer_id' => 'sometimes|required|integer',
                'description' => 'nullable|string',
                'due_date' => 'sometimes|required|date',
                'line_items' => 'sometimes|required|array|min:1',
                'line_items.*.description' => 'required|string|max:255',
                'line_items.*.quantity' => 'required|numeric|min:0.01',
                'line_items.*.price' => 'required|numeric|min:0',
                'status' => 'sometimes|in:draft,sent,paid,overdue,cancelled'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check if invoice exists and belongs to user
            $checkResponse = $this->client->get($this->supabaseUrl . '/rest/v1/invoices', [
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

            $existingInvoices = json_decode($checkResponse->getBody()->getContents(), true);

            if (empty($existingInvoices)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invoice not found'
                ], 404);
            }

            $existingInvoice = $existingInvoices[0];

            // Check if invoice can be edited (not paid)
            if (in_array($existingInvoice['status'], ['paid']) && !$request->has('status')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot edit paid invoices'
                ], 422);
            }

            $updateData = [];

            if ($request->has('description')) {
                $updateData['description'] = $request->input('description');
            }

            if ($request->has('due_date')) {
                $updateData['due_date'] = $request->input('due_date');
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

            $response = $this->client->patch($this->supabaseUrl . '/rest/v1/invoices', [
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

            $invoice = json_decode($response->getBody()->getContents(), true);

            return response()->json([
                'success' => true,
                'message' => 'Invoice updated successfully',
                'data' => $invoice[0] ?? null
            ]);

        } catch (\Exception $e) {
            \Log::error('API Invoice Update Error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update invoice',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Remove the specified invoice.
     *
     * @OA\Delete(
     *     path="/api/invoices/{id}",
     *     summary="Delete an invoice",
     *     tags={"Invoices"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Invoice deleted successfully"
     *     )
     * )
     */
    public function destroy(Request $request, $id)
    {
        try {
            $user = $request->user();

            // Check if invoice exists and belongs to user
            $checkResponse = $this->client->get($this->supabaseUrl . '/rest/v1/invoices', [
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

            $existingInvoices = json_decode($checkResponse->getBody()->getContents(), true);

            if (empty($existingInvoices)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invoice not found'
                ], 404);
            }

            $existingInvoice = $existingInvoices[0];

            // Check if invoice can be deleted (only draft invoices)
            if (!in_array($existingInvoice['status'], ['draft', 'cancelled'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only draft or cancelled invoices can be deleted'
                ], 422);
            }

            // Delete the invoice
            $response = $this->client->delete($this->supabaseUrl . '/rest/v1/invoices', [
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
                'message' => 'Invoice deleted successfully',
                'data' => [
                    'id' => (int)$id,
                    'deleted_at' => now()->toISOString()
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('API Invoice Delete Error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete invoice',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get invoice statistics
     *
     * @OA\Get(
     *     path="/api/invoices/stats",
     *     summary="Get invoice statistics",
     *     tags={"Invoices"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Invoice statistics retrieved successfully"
     *     )
     * )
     */
    public function stats(Request $request)
    {
        try {
            $user = $request->user();

            // Update overdue invoices first
            $this->updateOverdueInvoices($user['id']);

            // Get total invoices
            $totalResponse = $this->client->get($this->supabaseUrl . '/rest/v1/invoices', [
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

            $totalInvoices = $totalResponse->getHeader('Content-Range')[0] ?? '0';
            $totalInvoices = explode('/', $totalInvoices)[1] ?? 0;

            // Get invoices by status
            $statuses = ['draft', 'sent', 'paid', 'overdue', 'cancelled'];
            $statusCounts = [];
            $totalValue = 0;
            $paidValue = 0;
            $outstandingValue = 0;

            foreach ($statuses as $status) {
                $statusResponse = $this->client->get($this->supabaseUrl . '/rest/v1/invoices', [
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

            // Get total and paid values
            $valueResponse = $this->client->get($this->supabaseUrl . '/rest/v1/invoices', [
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

            $invoices = json_decode($valueResponse->getBody()->getContents(), true);
            
            foreach ($invoices as $invoice) {
                $amount = floatval($invoice['amount'] ?? 0);
                $totalValue += $amount;
                if ($invoice['status'] === 'paid') {
                    $paidValue += $amount;
                } else if (in_array($invoice['status'], ['sent', 'overdue'])) {
                    $outstandingValue += $amount;
                }
            }

            $paymentRate = $totalInvoices > 0 ? ($statusCounts['paid'] / $totalInvoices) * 100 : 0;

            return response()->json([
                'success' => true,
                'message' => 'Invoice statistics retrieved successfully',
                'data' => [
                    'total_invoices' => (int)$totalInvoices,
                    'draft_invoices' => $statusCounts['draft'],
                    'sent_invoices' => $statusCounts['sent'],
                    'paid_invoices' => $statusCounts['paid'],
                    'overdue_invoices' => $statusCounts['overdue'],
                    'cancelled_invoices' => $statusCounts['cancelled'],
                    'total_value' => $totalValue,
                    'paid_value' => $paidValue,
                    'outstanding_value' => $outstandingValue,
                    'payment_rate' => round($paymentRate, 2),
                    'average_invoice_value' => $totalInvoices > 0 ? round($totalValue / $totalInvoices, 2) : 0,
                    'generated_at' => now()->toISOString()
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('API Invoice Stats Error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve invoice statistics',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Update invoice status
     *
     * @OA\Patch(
     *     path="/api/invoices/{id}/status",
     *     summary="Update invoice status",
     *     tags={"Invoices"},
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
     *             @OA\Property(property="status", type="string", enum={"draft","sent","paid","overdue","cancelled"})
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Invoice status updated successfully"
     *     )
     * )
     */
    public function updateStatus(Request $request, $id)
    {
        try {
            $user = $request->user();

            $validator = Validator::make($request->all(), [
                'status' => 'required|in:draft,sent,paid,overdue,cancelled'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check if invoice exists and belongs to user
            $checkResponse = $this->client->get($this->supabaseUrl . '/rest/v1/invoices', [
                'headers' => [
                    'apikey' => $this->supabaseKey,
                    'Authorization' => 'Bearer ' . $this->supabaseKey,
                    'Content-Type' => 'application/json'
                ],
                'query' => [
                    'id' => 'eq.' . $id,
                    'user_id' => 'eq.' . $user['id'],
                    'select' => 'id,status,amount'
                ]
            ]);

            $existingInvoices = json_decode($checkResponse->getBody()->getContents(), true);

            if (empty($existingInvoices)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invoice not found'
                ], 404);
            }

            $existingInvoice = $existingInvoices[0];
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
                case 'paid':
                    $updateData['paid_at'] = now()->toISOString();
                    
                    // Create transaction record for manual payment
                    $transactionData = [
                        'user_id' => $user['id'],
                        'invoice_id' => $id,
                        'customer_id' => $existingInvoice['customer_id'] ?? null,
                        'amount' => $existingInvoice['amount'],
                        'status' => 'completed',
                        'payment_method' => 'manual',
                        'currency' => 'USD',
                        'created_at' => now()->toISOString(),
                        'updated_at' => now()->toISOString()
                    ];

                    try {
                        $this->client->post($this->supabaseUrl . '/rest/v1/transactions', [
                            'headers' => [
                                'apikey' => $this->supabaseKey,
                                'Authorization' => 'Bearer ' . $this->supabaseKey,
                                'Content-Type' => 'application/json'
                            ],
                            'json' => $transactionData
                        ]);
                    } catch (\Exception $e) {
                        \Log::warning('Failed to create transaction for manual payment: ' . $e->getMessage());
                    }
                    break;
            }

            $response = $this->client->patch($this->supabaseUrl . '/rest/v1/invoices', [
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

            $invoice = json_decode($response->getBody()->getContents(), true);

            return response()->json([
                'success' => true,
                'message' => "Invoice status updated to {$newStatus}",
                'data' => $invoice[0] ?? null
            ]);

        } catch (\Exception $e) {
            \Log::error('API Invoice Status Update Error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update invoice status',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Create Stripe payment session for invoice
     *
     * @OA\Post(
     *     path="/api/invoices/{id}/payment",
     *     summary="Create Stripe payment session",
     *     tags={"Invoices"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Payment session created successfully"
     *     )
     * )
     */
    public function createPayment(Request $request, $id)
    {
        try {
            $user = $request->user();

            // Check if invoice exists and belongs to user
            $checkResponse = $this->client->get($this->supabaseUrl . '/rest/v1/invoices', [
                'headers' => [
                    'apikey' => $this->supabaseKey,
                    'Authorization' => 'Bearer ' . $this->supabaseKey,
                    'Content-Type' => 'application/json'
                ],
                'query' => [
                    'id' => 'eq.' . $id,
                    'user_id' => 'eq.' . $user['id'],
                    'select' => 'id,invoice_number,amount,status,customer:customer_id(id,name,email)'
                ]
            ]);

            $existingInvoices = json_decode($checkResponse->getBody()->getContents(), true);

            if (empty($existingInvoices)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invoice not found'
                ], 404);
            }

            $invoice = $existingInvoices[0];

            if ($invoice['status'] === 'paid') {
                return response()->json([
                    'success' => false,
                    'message' => 'Invoice is already paid'
                ], 422);
            }

            // Create Stripe checkout session (mock implementation)
            $paymentUrl = url("/invoices/{$id}/payment");
            
            return response()->json([
                'success' => true,
                'message' => 'Payment session created successfully',
                'data' => [
                    'payment_url' => $paymentUrl,
                    'invoice_id' => (int)$id,
                    'amount' => $invoice['amount'],
                    'currency' => 'USD',
                    'expires_at' => now()->addHours(24)->toISOString()
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('API Invoice Payment Creation Error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create payment session',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Update overdue invoices
     */
    private function updateOverdueInvoices($userId)
    {
        try {
            $today = now()->toDateString();
            
            $this->client->patch($this->supabaseUrl . '/rest/v1/invoices', [
                'headers' => [
                    'apikey' => $this->supabaseKey,
                    'Authorization' => 'Bearer ' . $this->supabaseKey,
                    'Content-Type' => 'application/json'
                ],
                'query' => [
                    'user_id' => 'eq.' . $userId,
                    'status' => 'eq.sent',
                    'due_date' => 'lt.' . $today
                ],
                'json' => [
                    'status' => 'overdue',
                    'updated_at' => now()->toISOString()
                ]
            ]);
        } catch (\Exception $e) {
            \Log::warning('Failed to update overdue invoices: ' . $e->getMessage());
        }
    }
} 
<?php

namespace App\Http\Controllers;

use App\Services\SupabaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class SupabaseCustomerController extends Controller
{
    protected $supabase;

    public function __construct(SupabaseService $supabase)
    {
        $this->supabase = $supabase;
    }

    /**
     * Display a listing of customers with search and pagination
     */
    public function index(Request $request)
    {
        try {
            // Get all customers from Supabase
            $customers = $this->supabase->query('customers', '*');
            
            if (!is_array($customers)) {
                $customers = [];
            }

            // Apply search filter
            $search = $request->get('search');
            if ($search) {
                $customers = array_filter($customers, function ($customer) use ($search) {
                    return stripos($customer['name'], $search) !== false ||
                           stripos($customer['email'], $search) !== false ||
                           stripos($customer['company'], $search) !== false;
                });
            }

            // Apply status filter
            $status = $request->get('status');
            if ($status && $status !== 'all') {
                $customers = array_filter($customers, function ($customer) use ($status) {
                    return $customer['status'] === $status;
                });
            }

            // Sort customers
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            
            usort($customers, function ($a, $b) use ($sortBy, $sortOrder) {
                $valueA = $a[$sortBy] ?? '';
                $valueB = $b[$sortBy] ?? '';
                
                if ($sortOrder === 'desc') {
                    return $valueB <=> $valueA;
                }
                return $valueA <=> $valueB;
            });

            // Simple pagination
            $perPage = 15;
            $page = $request->get('page', 1);
            $offset = ($page - 1) * $perPage;
            $paginatedCustomers = array_slice($customers, $offset, $perPage);
            
            // Calculate stats
            $stats = [
                'total' => count($customers),
                'active' => count(array_filter($customers, fn($c) => $c['status'] === 'active')),
                'inactive' => count(array_filter($customers, fn($c) => $c['status'] === 'inactive')),
            ];

            // For API requests
            if ($request->expectsJson()) {
                return response()->json([
                    'customers' => $paginatedCustomers,
                    'stats' => $stats,
                    'pagination' => [
                        'current_page' => $page,
                        'per_page' => $perPage,
                        'total' => count($customers),
                        'total_pages' => ceil(count($customers) / $perPage)
                    ]
                ]);
            }

            return view('customers.index', compact('paginatedCustomers', 'stats', 'search', 'status'));
            
        } catch (\Exception $e) {
            \Log::error('Customer index error: ' . $e->getMessage());
            
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Failed to load customers'], 500);
            }
            
            return back()->with('error', 'Failed to load customers: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for creating a new customer
     */
    public function create()
    {
        return view('customers.create');
    }

    /**
     * Store a newly created customer
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'company' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:500',
            'status' => 'required|in:active,inactive',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            return back()->withErrors($validator)->withInput();
        }

        try {
            // Check if email already exists
            $existingCustomers = $this->supabase->query('customers', '*', ['email' => $request->email]);
            if (!empty($existingCustomers) && is_array($existingCustomers) && count($existingCustomers) > 0) {
                if ($request->expectsJson()) {
                    return response()->json(['error' => 'Email already exists'], 422);
                }
                return back()->withErrors(['email' => 'Email already exists'])->withInput();
            }

            // Create customer data
            $customerData = [
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'company' => $request->company,
                'address' => $request->address,
                'status' => $request->status,
                'notes' => $request->notes,
                'created_at' => now()->toISOString(),
                'updated_at' => now()->toISOString(),
            ];

            $result = $this->supabase->insert('customers', $customerData);

            if (!$result || (is_array($result) && isset($result['error']))) {
                throw new \Exception('Failed to create customer in database');
            }

            // Send welcome email
            $this->sendWelcomeEmail($request->email, $request->name);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Customer created successfully',
                    'customer' => $result
                ], 201);
            }

            return redirect()->route('customers.index')->with('success', 'Customer created successfully!');
            
        } catch (\Exception $e) {
            \Log::error('Customer creation error: ' . $e->getMessage());
            
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Failed to create customer'], 500);
            }
            
            return back()->withErrors(['error' => 'Failed to create customer: ' . $e->getMessage()])->withInput();
        }
    }

    /**
     * Display the specified customer
     */
    public function show($id, Request $request)
    {
        try {
            $customers = $this->supabase->query('customers', '*', ['id' => $id]);
            
            if (empty($customers) || !is_array($customers) || count($customers) === 0) {
                if ($request->expectsJson()) {
                    return response()->json(['error' => 'Customer not found'], 404);
                }
                return redirect()->route('customers.index')->with('error', 'Customer not found');
            }

            $customer = $customers[0];

            // Get related proposals and invoices
            $proposals = $this->supabase->query('proposals', '*', ['customer_id' => $id]) ?: [];
            $invoices = $this->supabase->query('invoices', '*', ['customer_id' => $id]) ?: [];

            if ($request->expectsJson()) {
                return response()->json([
                    'customer' => $customer,
                    'proposals' => $proposals,
                    'invoices' => $invoices
                ]);
            }

            return view('customers.show', compact('customer', 'proposals', 'invoices'));
            
        } catch (\Exception $e) {
            \Log::error('Customer show error: ' . $e->getMessage());
            
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Failed to load customer'], 500);
            }
            
            return redirect()->route('customers.index')->with('error', 'Failed to load customer');
        }
    }

    /**
     * Show the form for editing the specified customer
     */
    public function edit($id)
    {
        try {
            $customers = $this->supabase->query('customers', '*', ['id' => $id]);
            
            if (empty($customers) || !is_array($customers) || count($customers) === 0) {
                return redirect()->route('customers.index')->with('error', 'Customer not found');
            }

            $customer = $customers[0];
            return view('customers.edit', compact('customer'));
            
        } catch (\Exception $e) {
            \Log::error('Customer edit error: ' . $e->getMessage());
            return redirect()->route('customers.index')->with('error', 'Failed to load customer');
        }
    }

    /**
     * Update the specified customer
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'company' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:500',
            'status' => 'required|in:active,inactive',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            return back()->withErrors($validator)->withInput();
        }

        try {
            // Check if customer exists
            $customers = $this->supabase->query('customers', '*', ['id' => $id]);
            if (empty($customers) || !is_array($customers) || count($customers) === 0) {
                if ($request->expectsJson()) {
                    return response()->json(['error' => 'Customer not found'], 404);
                }
                return redirect()->route('customers.index')->with('error', 'Customer not found');
            }

            // Check if email is taken by another customer
            $existingCustomers = $this->supabase->query('customers', '*', ['email' => $request->email]);
            if (!empty($existingCustomers) && is_array($existingCustomers)) {
                foreach ($existingCustomers as $existing) {
                    if ($existing['id'] != $id) {
                        if ($request->expectsJson()) {
                            return response()->json(['error' => 'Email already exists'], 422);
                        }
                        return back()->withErrors(['email' => 'Email already exists'])->withInput();
                    }
                }
            }

            // Update customer data
            $updateData = [
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'company' => $request->company,
                'address' => $request->address,
                'status' => $request->status,
                'notes' => $request->notes,
                'updated_at' => now()->toISOString(),
            ];

            $result = $this->supabase->update('customers', $id, $updateData);

            if (!$result) {
                throw new \Exception('Failed to update customer in database');
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Customer updated successfully',
                    'customer' => array_merge($customers[0], $updateData)
                ]);
            }

            return redirect()->route('customers.index')->with('success', 'Customer updated successfully!');
            
        } catch (\Exception $e) {
            \Log::error('Customer update error: ' . $e->getMessage());
            
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Failed to update customer'], 500);
            }
            
            return back()->withErrors(['error' => 'Failed to update customer: ' . $e->getMessage()])->withInput();
        }
    }

    /**
     * Remove the specified customer
     */
    public function destroy($id, Request $request)
    {
        try {
            // Check if customer exists
            $customers = $this->supabase->query('customers', '*', ['id' => $id]);
            if (empty($customers) || !is_array($customers) || count($customers) === 0) {
                if ($request->expectsJson()) {
                    return response()->json(['error' => 'Customer not found'], 404);
                }
                return redirect()->route('customers.index')->with('error', 'Customer not found');
            }

            // Soft delete by setting deleted_at
            $result = $this->supabase->update('customers', $id, [
                'deleted_at' => now()->toISOString(),
                'updated_at' => now()->toISOString(),
            ]);

            if (!$result) {
                throw new \Exception('Failed to delete customer');
            }

            if ($request->expectsJson()) {
                return response()->json(['message' => 'Customer deleted successfully']);
            }

            return redirect()->route('customers.index')->with('success', 'Customer deleted successfully!');
            
        } catch (\Exception $e) {
            \Log::error('Customer deletion error: ' . $e->getMessage());
            
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Failed to delete customer'], 500);
            }
            
            return redirect()->route('customers.index')->with('error', 'Failed to delete customer');
        }
    }

    /**
     * Toggle customer status
     */
    public function toggleStatus($id, Request $request)
    {
        try {
            $customers = $this->supabase->query('customers', '*', ['id' => $id]);
            if (empty($customers) || !is_array($customers) || count($customers) === 0) {
                if ($request->expectsJson()) {
                    return response()->json(['error' => 'Customer not found'], 404);
                }
                return redirect()->route('customers.index')->with('error', 'Customer not found');
            }

            $customer = $customers[0];
            $newStatus = $customer['status'] === 'active' ? 'inactive' : 'active';

            $result = $this->supabase->update('customers', $id, [
                'status' => $newStatus,
                'updated_at' => now()->toISOString(),
            ]);

            if (!$result) {
                throw new \Exception('Failed to update customer status');
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Customer status updated successfully',
                    'status' => $newStatus
                ]);
            }

            return redirect()->route('customers.index')->with('success', 'Customer status updated successfully!');
            
        } catch (\Exception $e) {
            \Log::error('Customer status toggle error: ' . $e->getMessage());
            
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Failed to update status'], 500);
            }
            
            return redirect()->route('customers.index')->with('error', 'Failed to update customer status');
        }
    }

    /**
     * Bulk actions for customers
     */
    public function bulkAction(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'action' => 'required|in:activate,deactivate,delete',
            'customer_ids' => 'required|array|min:1',
            'customer_ids.*' => 'integer'
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json(['errors' => $validator->errors()], 422);
            }
            return back()->withErrors($validator);
        }

        try {
            $action = $request->action;
            $customerIds = $request->customer_ids;
            $successCount = 0;

            foreach ($customerIds as $id) {
                try {
                    switch ($action) {
                        case 'activate':
                            $this->supabase->update('customers', $id, [
                                'status' => 'active',
                                'updated_at' => now()->toISOString()
                            ]);
                            break;
                        case 'deactivate':
                            $this->supabase->update('customers', $id, [
                                'status' => 'inactive',
                                'updated_at' => now()->toISOString()
                            ]);
                            break;
                        case 'delete':
                            $this->supabase->update('customers', $id, [
                                'deleted_at' => now()->toISOString(),
                                'updated_at' => now()->toISOString()
                            ]);
                            break;
                    }
                    $successCount++;
                } catch (\Exception $e) {
                    \Log::error("Bulk action failed for customer {$id}: " . $e->getMessage());
                }
            }

            $message = "Bulk action completed successfully for {$successCount} customers.";

            if ($request->expectsJson()) {
                return response()->json(['message' => $message, 'affected_count' => $successCount]);
            }

            return redirect()->route('customers.index')->with('success', $message);
            
        } catch (\Exception $e) {
            \Log::error('Bulk action error: ' . $e->getMessage());
            
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Bulk action failed'], 500);
            }
            
            return redirect()->route('customers.index')->with('error', 'Bulk action failed');
        }
    }

    /**
     * Export customers to CSV
     */
    public function export(Request $request)
    {
        try {
            $customers = $this->supabase->query('customers', '*');
            
            if (!is_array($customers)) {
                $customers = [];
            }

            // Apply filters if provided
            $search = $request->get('search');
            if ($search) {
                $customers = array_filter($customers, function ($customer) use ($search) {
                    return stripos($customer['name'], $search) !== false ||
                           stripos($customer['email'], $search) !== false ||
                           stripos($customer['company'], $search) !== false;
                });
            }

            $status = $request->get('status');
            if ($status && $status !== 'all') {
                $customers = array_filter($customers, function ($customer) use ($status) {
                    return $customer['status'] === $status;
                });
            }

            // Prepare CSV data
            $csvData = [];
            $csvData[] = ['ID', 'Name', 'Email', 'Phone', 'Company', 'Status', 'Address', 'Notes', 'Created Date'];

            foreach ($customers as $customer) {
                $csvData[] = [
                    $customer['id'],
                    $customer['name'],
                    $customer['email'],
                    $customer['phone'] ?? '',
                    $customer['company'] ?? '',
                    $customer['status'],
                    $customer['address'] ?? '',
                    $customer['notes'] ?? '',
                    $customer['created_at']
                ];
            }

            $filename = 'customers_export_' . date('Y-m-d_H-i-s') . '.csv';
            
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            ];

            return response()->stream(function () use ($csvData) {
                $file = fopen('php://output', 'w');
                foreach ($csvData as $row) {
                    fputcsv($file, $row);
                }
                fclose($file);
            }, 200, $headers);
            
        } catch (\Exception $e) {
            \Log::error('Customer export error: ' . $e->getMessage());
            return redirect()->route('customers.index')->with('error', 'Failed to export customers');
        }
    }

    /**
     * Send welcome email to customer
     */
    private function sendWelcomeEmail($email, $name)
    {
        try {
            Mail::send([], [], function ($message) use ($email, $name) {
                $message->to($email)
                        ->subject('Welcome to Connectly CRM!')
                        ->html("
                            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;'>
                                <h2 style='color: #4f46e5; text-align: center;'>Welcome to Connectly!</h2>
                                <div style='background: #f8fafc; padding: 20px; border-radius: 10px; margin: 20px 0;'>
                                    <p style='font-size: 18px; color: #1f2937;'>Hi {$name},</p>
                                    <p style='color: #64748b;'>Welcome to Connectly - your modern CRM solution!</p>
                                    <p style='color: #64748b;'>You've been added to our customer database. We look forward to working with you!</p>
                                </div>
                                <p style='color: #64748b; text-align: center; font-size: 14px;'>Thank you for choosing Connectly!</p>
                            </div>
                        ");
            });
        } catch (\Exception $e) {
            \Log::error('Welcome email failed: ' . $e->getMessage());
            // Don't throw - customer creation should still succeed
        }
    }
} 
<?php

namespace App\Http\Controllers;

use App\Services\SupabaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

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

            // Filter by search if provided
            if ($request->filled('search')) {
                $search = strtolower($request->search);
                $customers = array_filter($customers, function($customer) use ($search) {
                    return str_contains(strtolower($customer['name'] ?? ''), $search) ||
                           str_contains(strtolower($customer['email'] ?? ''), $search) ||
                           str_contains(strtolower($customer['company'] ?? ''), $search);
                });
            }

            // Filter by status if provided
            if ($request->filled('status')) {
                $customers = array_filter($customers, function($customer) use ($request) {
                    return ($customer['status'] ?? 'active') === $request->status;
                });
            }

            // Sort customers by created_at (newest first)
            usort($customers, function($a, $b) {
                return strtotime($b['created_at'] ?? '0') - strtotime($a['created_at'] ?? '0');
            });

            // Simple pagination
            $perPage = 10;
            $page = $request->get('page', 1);
            $offset = ($page - 1) * $perPage;
            $paginatedCustomers = array_slice($customers, $offset, $perPage);
            $totalPages = ceil(count($customers) / $perPage);

            // Calculate statistics
            $stats = [
                'total' => count($customers),
                'active' => count(array_filter($customers, fn($c) => ($c['status'] ?? 'active') === 'active')),
                'inactive' => count(array_filter($customers, fn($c) => ($c['status'] ?? 'active') === 'inactive')),
            ];

            return view('customers.index', [
                'customers' => $paginatedCustomers,
                'stats' => $stats,
                'currentPage' => $page,
                'totalPages' => $totalPages,
                'search' => $request->search,
                'status' => $request->status
            ]);

        } catch (\Exception $e) {
            \Log::error('Customer index error: ' . $e->getMessage());
            return view('customers.index', [
                'customers' => [],
                'stats' => ['total' => 0, 'active' => 0, 'inactive' => 0],
                'error' => 'Failed to load customers: ' . $e->getMessage()
            ]);
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
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:50',
            'company' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:500',
            'status' => 'required|in:active,inactive',
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            // Check if email already exists
            $existingCustomers = $this->supabase->query('customers', '*', ['email' => $validated['email']]);
            if (!empty($existingCustomers) && is_array($existingCustomers) && count($existingCustomers) > 0) {
                return back()->withInput()->withErrors(['email' => 'A customer with this email already exists.']);
            }

            // Prepare customer data
            $customerData = [
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'company' => $validated['company'],
                'address' => $validated['address'],
                'status' => $validated['status'],
                'notes' => $validated['notes'],
                'created_at' => now()->toISOString(),
                'updated_at' => now()->toISOString()
            ];

            // Create customer in Supabase
            $result = $this->supabase->insert('customers', $customerData);

            if (!$result || (is_array($result) && isset($result['error']))) {
                $errorMessage = is_array($result) && isset($result['error']) ? $result['error']['message'] : 'Failed to create customer';
                return back()->withInput()->withErrors(['email' => $errorMessage]);
            }

            // Send welcome email
            $this->sendWelcomeEmail($validated['email'], $validated['name']);

            return redirect()->route('customers.index')
                ->with('success', 'Customer created successfully and welcome email sent!');

        } catch (\Exception $e) {
            \Log::error('Customer creation error: ' . $e->getMessage());
            return back()->withInput()->withErrors(['email' => 'Failed to create customer: ' . $e->getMessage()]);
        }
    }

    /**
     * Display the specified customer
     */
    public function show($id)
    {
        try {
            $customers = $this->supabase->query('customers', '*', ['id' => $id]);
            
            if (empty($customers) || !is_array($customers) || count($customers) === 0) {
                return redirect()->route('customers.index')
                    ->with('error', 'Customer not found.');
            }

            $customer = $customers[0];

            // Get related proposals and invoices (when those features are implemented)
            $proposals = []; // $this->supabase->query('proposals', '*', ['customer_id' => $id]);
            $invoices = [];  // $this->supabase->query('invoices', '*', ['customer_id' => $id]);

            return view('customers.show', [
                'customer' => $customer,
                'proposals' => $proposals,
                'invoices' => $invoices
            ]);

        } catch (\Exception $e) {
            \Log::error('Customer show error: ' . $e->getMessage());
            return redirect()->route('customers.index')
                ->with('error', 'Failed to load customer: ' . $e->getMessage());
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
                return redirect()->route('customers.index')
                    ->with('error', 'Customer not found.');
            }

            $customer = $customers[0];
            return view('customers.edit', ['customer' => $customer]);

        } catch (\Exception $e) {
            \Log::error('Customer edit error: ' . $e->getMessage());
            return redirect()->route('customers.index')
                ->with('error', 'Failed to load customer: ' . $e->getMessage());
        }
    }

    /**
     * Update the specified customer
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:50',
            'company' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:500',
            'status' => 'required|in:active,inactive',
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            // Check if email already exists for other customers
            $existingCustomers = $this->supabase->query('customers', '*', ['email' => $validated['email']]);
            if (!empty($existingCustomers) && is_array($existingCustomers)) {
                foreach ($existingCustomers as $existing) {
                    if ($existing['id'] != $id) {
                        return back()->withInput()->withErrors(['email' => 'A customer with this email already exists.']);
                    }
                }
            }

            // Update customer data
            $updateData = [
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'company' => $validated['company'],
                'address' => $validated['address'],
                'status' => $validated['status'],
                'notes' => $validated['notes'],
                'updated_at' => now()->toISOString()
            ];

            $result = $this->supabase->update('customers', $id, $updateData);

            if (!$result) {
                return back()->withInput()->withErrors(['email' => 'Failed to update customer.']);
            }

            return redirect()->route('customers.index')
                ->with('success', 'Customer updated successfully!');

        } catch (\Exception $e) {
            \Log::error('Customer update error: ' . $e->getMessage());
            return back()->withInput()->withErrors(['email' => 'Failed to update customer: ' . $e->getMessage()]);
        }
    }

    /**
     * Remove the specified customer (soft delete)
     */
    public function destroy($id)
    {
        try {
            // Soft delete by setting deleted_at timestamp
            $result = $this->supabase->update('customers', $id, [
                'deleted_at' => now()->toISOString(),
                'status' => 'inactive'
            ]);

            if (!$result) {
                return redirect()->route('customers.index')
                    ->with('error', 'Failed to delete customer.');
            }

            return redirect()->route('customers.index')
                ->with('success', 'Customer deleted successfully!');

        } catch (\Exception $e) {
            \Log::error('Customer delete error: ' . $e->getMessage());
            return redirect()->route('customers.index')
                ->with('error', 'Failed to delete customer: ' . $e->getMessage());
        }
    }

    /**
     * Toggle customer status (active/inactive)
     */
    public function toggleStatus($id)
    {
        try {
            $customers = $this->supabase->query('customers', '*', ['id' => $id]);
            
            if (empty($customers) || !is_array($customers) || count($customers) === 0) {
                return response()->json(['error' => 'Customer not found'], 404);
            }

            $customer = $customers[0];
            $newStatus = ($customer['status'] ?? 'active') === 'active' ? 'inactive' : 'active';

            $result = $this->supabase->update('customers', $id, [
                'status' => $newStatus,
                'updated_at' => now()->toISOString()
            ]);

            if (!$result) {
                return response()->json(['error' => 'Failed to update status'], 500);
            }

            return response()->json([
                'success' => true,
                'status' => $newStatus,
                'message' => "Customer status changed to {$newStatus}"
            ]);

        } catch (\Exception $e) {
            \Log::error('Customer toggle status error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to toggle status: ' . $e->getMessage()], 500);
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

            // Apply same filters as index
            if ($request->filled('search')) {
                $search = strtolower($request->search);
                $customers = array_filter($customers, function($customer) use ($search) {
                    return str_contains(strtolower($customer['name'] ?? ''), $search) ||
                           str_contains(strtolower($customer['email'] ?? ''), $search) ||
                           str_contains(strtolower($customer['company'] ?? ''), $search);
                });
            }

            if ($request->filled('status')) {
                $customers = array_filter($customers, function($customer) use ($request) {
                    return ($customer['status'] ?? 'active') === $request->status;
                });
            }

            $filename = 'customers_export_' . date('Y-m-d_H-i-s') . '.csv';
            
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            ];

            return response()->stream(function () use ($customers) {
                $file = fopen('php://output', 'w');
                
                // CSV Headers
                fputcsv($file, ['ID', 'Name', 'Email', 'Phone', 'Company', 'Status', 'Address', 'Notes', 'Created At']);
                
                // CSV Data
                foreach ($customers as $customer) {
                    fputcsv($file, [
                        $customer['id'] ?? '',
                        $customer['name'] ?? '',
                        $customer['email'] ?? '',
                        $customer['phone'] ?? '',
                        $customer['company'] ?? '',
                        $customer['status'] ?? 'active',
                        $customer['address'] ?? '',
                        $customer['notes'] ?? '',
                        $customer['created_at'] ?? '',
                    ]);
                }
                
                fclose($file);
            }, 200, $headers);

        } catch (\Exception $e) {
            \Log::error('Customer export error: ' . $e->getMessage());
            return redirect()->route('customers.index')
                ->with('error', 'Failed to export customers: ' . $e->getMessage());
        }
    }

    /**
     * Send welcome email to new customer
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
                                    <p style='color: #64748b;'>Welcome to our CRM system! We're excited to work with you.</p>
                                    <p style='color: #64748b;'>Our team will be in touch soon to discuss how we can help you achieve your goals.</p>
                                </div>
                                <div style='text-align: center; margin: 30px 0;'>
                                    <p style='color: #64748b;'>If you have any questions, feel free to reach out to us!</p>
                                </div>
                                <p style='color: #64748b; text-align: center; font-size: 14px;'>
                                    Best regards,<br>The Connectly Team
                                </p>
                            </div>
                        ");
            });
        } catch (\Exception $e) {
            \Log::error('Welcome email failed: ' . $e->getMessage());
            // Don't throw error - customer creation should still succeed
        }
    }
} 
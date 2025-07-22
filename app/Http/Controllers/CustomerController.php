<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Customer::with(['proposals', 'invoices', 'transactions']);
        
        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('company_name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }
        
        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        // Sort options
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);
        
        $customers = $query->paginate(15)->withQueryString();
        
        // Get statistics
        $stats = [
            'total' => Customer::count(),
            'active' => Customer::where('status', 'active')->count(),
            'inactive' => Customer::where('status', 'inactive')->count(),
        ];
        
        return view('customers.index', compact('customers', 'stats'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('customers.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:customers',
            'company_name' => 'nullable|string|max:255',
            'company' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'status' => 'required|in:active,inactive',
            'notes' => 'nullable|string|max:1000',
        ]);

        $customer = Customer::create($validated);

        // Send welcome email
        try {
            \Mail::send([], [], function ($message) use ($customer) {
                $message->to($customer->email)
                        ->subject('Welcome to Connectly CRM!')
                        ->html("
                            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;'>
                                <h2 style='color: #4f46e5; text-align: center;'>Welcome to Connectly!</h2>
                                <p>Hi {$customer->name},</p>
                                <p>You've been added to our CRM system. We look forward to working with you!</p>
                                <p>Best regards,<br>The Connectly Team</p>
                            </div>
                        ");
            });
        } catch (\Exception $e) {
            \Log::error('Welcome email failed: ' . $e->getMessage());
        }

        return redirect()->route('customers.index')
            ->with('success', 'Customer created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Customer $customer)
    {
        $customer->load(['proposals.customer', 'invoices.customer', 'transactions']);
        
        // Get recent activities
        $recentProposals = $customer->proposals()->latest()->take(5)->get();
        $recentInvoices = $customer->invoices()->latest()->take(5)->get();
        $recentTransactions = $customer->transactions()->latest()->take(5)->get();
        
        return view('customers.show', compact('customer', 'recentProposals', 'recentInvoices', 'recentTransactions'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Customer $customer)
    {
        return view('customers.edit', compact('customer'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:customers,email,' . $customer->id,
            'company_name' => 'nullable|string|max:255',
            'company' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'status' => 'required|in:active,inactive',
            'notes' => 'nullable|string|max:1000',
        ]);

        $customer->update($validated);

        return redirect()->route('customers.index')
            ->with('success', 'Customer updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Customer $customer)
    {
        // Check if customer has related records
        if ($customer->proposals()->count() > 0 || $customer->invoices()->count() > 0) {
            return redirect()->route('customers.index')
                ->with('error', 'Cannot delete customer with existing proposals or invoices. Set status to inactive instead.');
        }

        $customer->delete();

        return redirect()->route('customers.index')
            ->with('success', 'Customer deleted successfully.');
    }

    /**
     * Toggle customer status (active/inactive)
     */
    public function toggleStatus(Customer $customer)
    {
        $newStatus = $customer->status === 'active' ? 'inactive' : 'active';
        $customer->update(['status' => $newStatus]);

        return response()->json([
            'success' => true,
            'status' => $newStatus,
            'message' => "Customer status changed to {$newStatus}"
        ]);
    }

    /**
     * Bulk actions for customers
     */
    public function bulkAction(Request $request)
    {
        $action = $request->input('action');
        $customerIds = $request->input('customers', []);

        if (empty($customerIds)) {
            return redirect()->back()->with('error', 'No customers selected.');
        }

        switch ($action) {
            case 'activate':
                Customer::whereIn('id', $customerIds)->update(['status' => 'active']);
                $message = 'Selected customers activated successfully.';
                break;
            case 'deactivate':
                Customer::whereIn('id', $customerIds)->update(['status' => 'inactive']);
                $message = 'Selected customers deactivated successfully.';
                break;
            case 'delete':
                Customer::whereIn('id', $customerIds)
                    ->whereDoesntHave('proposals')
                    ->whereDoesntHave('invoices')
                    ->delete();
                $message = 'Selected customers deleted successfully.';
                break;
            default:
                $message = 'Invalid action selected.';
        }

        return redirect()->back()->with('success', $message);
    }

    /**
     * Export customers to CSV
     */
    public function export(Request $request)
    {
        $query = Customer::query();

        // Apply same filters as index
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('company_name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $customers = $query->get();

        $csvData = [];
        $csvData[] = ['Name', 'Email', 'Company', 'Phone', 'Status', 'Address', 'Created Date'];

        foreach ($customers as $customer) {
            $csvData[] = [
                $customer->name,
                $customer->email,
                $customer->company_name ?? $customer->company,
                $customer->phone,
                $customer->status,
                $customer->address,
                $customer->created_at->format('Y-m-d H:i:s'),
            ];
        }

        $filename = 'customers_' . date('Y-m-d_H-i-s') . '.csv';
        
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
    }
}

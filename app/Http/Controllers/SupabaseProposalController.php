<?php

namespace App\Http\Controllers;

use App\Services\SupabaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Barryvdh\DomPDF\Facade\Pdf;

class SupabaseProposalController extends Controller
{
    protected $supabase;

    public function __construct(SupabaseService $supabase)
    {
        $this->supabase = $supabase;
    }

    /**
     * Display a listing of proposals with search and pagination
     */
    public function index(Request $request)
    {
        try {
            // Get all proposals from Supabase
            $proposals = $this->supabase->query('proposals', '*');
            
            if (!is_array($proposals)) {
                $proposals = [];
            }

            // Get customers for linking
            $customers = $this->supabase->query('customers', 'id,name,email,company');
            $customersById = [];
            if (is_array($customers)) {
                foreach ($customers as $customer) {
                    $customersById[$customer['id']] = $customer;
                }
            }

            // Add customer data to proposals
            foreach ($proposals as &$proposal) {
                $proposal['customer'] = $customersById[$proposal['customer_id']] ?? null;
            }

            // Filter by search if provided
            if ($request->filled('search')) {
                $search = strtolower($request->search);
                $proposals = array_filter($proposals, function($proposal) use ($search) {
                    return str_contains(strtolower($proposal['title'] ?? ''), $search) ||
                           str_contains(strtolower($proposal['description'] ?? ''), $search) ||
                           str_contains(strtolower($proposal['customer']['name'] ?? ''), $search) ||
                           str_contains(strtolower($proposal['customer']['company'] ?? ''), $search);
                });
            }

            // Filter by status if provided
            if ($request->filled('status')) {
                $proposals = array_filter($proposals, function($proposal) use ($request) {
                    return ($proposal['status'] ?? 'draft') === $request->status;
                });
            }

            // Filter by customer if provided
            if ($request->filled('customer_id')) {
                $proposals = array_filter($proposals, function($proposal) use ($request) {
                    return $proposal['customer_id'] == $request->customer_id;
                });
            }

            // Sort proposals by created_at (newest first)
            usort($proposals, function($a, $b) {
                return strtotime($b['created_at'] ?? '0') - strtotime($a['created_at'] ?? '0');
            });

            // Simple pagination
            $perPage = 10;
            $page = $request->get('page', 1);
            $offset = ($page - 1) * $perPage;
            $paginatedProposals = array_slice($proposals, $offset, $perPage);
            $totalPages = ceil(count($proposals) / $perPage);

            // Calculate statistics
            $stats = [
                'total' => count($proposals),
                'draft' => count(array_filter($proposals, fn($p) => ($p['status'] ?? 'draft') === 'draft')),
                'sent' => count(array_filter($proposals, fn($p) => ($p['status'] ?? 'draft') === 'sent')),
                'accepted' => count(array_filter($proposals, fn($p) => ($p['status'] ?? 'draft') === 'accepted')),
                'rejected' => count(array_filter($proposals, fn($p) => ($p['status'] ?? 'draft') === 'rejected')),
                'total_value' => array_sum(array_column($proposals, 'total_amount'))
            ];

            return view('proposals.index', [
                'proposals' => $paginatedProposals,
                'customers' => $customers ?? [],
                'stats' => $stats,
                'currentPage' => $page,
                'totalPages' => $totalPages,
                'search' => $request->search,
                'status' => $request->status,
                'customer_id' => $request->customer_id
            ]);

        } catch (\Exception $e) {
            \Log::error('Proposal index error: ' . $e->getMessage());
            return view('proposals.index', [
                'proposals' => [],
                'customers' => [],
                'stats' => ['total' => 0, 'draft' => 0, 'sent' => 0, 'accepted' => 0, 'rejected' => 0, 'total_value' => 0],
                'error' => 'Failed to load proposals: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Show the form for creating a new proposal
     */
    public function create(Request $request)
    {
        try {
            // Get all active customers
            $customers = $this->supabase->query('customers', '*');
            if (!is_array($customers)) {
                $customers = [];
            }

            // Filter active customers only
            $customers = array_filter($customers, fn($c) => ($c['status'] ?? 'active') === 'active');

            $preselectedCustomer = null;
            if ($request->filled('customer_id')) {
                $preselectedCustomer = $request->customer_id;
            }

            return view('proposals.create', [
                'customers' => $customers,
                'preselectedCustomer' => $preselectedCustomer
            ]);

        } catch (\Exception $e) {
            \Log::error('Proposal create form error: ' . $e->getMessage());
            return redirect()->route('proposals.index')
                ->with('error', 'Failed to load proposal creation form: ' . $e->getMessage());
        }
    }

    /**
     * Store a newly created proposal
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|integer',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string|max:255',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.rate' => 'required|numeric|min:0',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'discount_amount' => 'nullable|numeric|min:0',
            'valid_until' => 'required|date|after:today',
            'terms_conditions' => 'nullable|string|max:2000',
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            // Verify customer exists
            $customers = $this->supabase->query('customers', '*', ['id' => $validated['customer_id']]);
            if (empty($customers) || !is_array($customers) || count($customers) === 0) {
                return back()->withInput()->withErrors(['customer_id' => 'Selected customer not found.']);
            }

            // Calculate totals
            $subtotal = 0;
            foreach ($validated['items'] as $item) {
                $subtotal += $item['quantity'] * $item['rate'];
            }

            $discountAmount = $validated['discount_amount'] ?? 0;
            $taxableAmount = $subtotal - $discountAmount;
            $taxAmount = $taxableAmount * (($validated['tax_rate'] ?? 0) / 100);
            $totalAmount = $taxableAmount + $taxAmount;

            // Generate proposal number
            $proposalNumber = 'PROP-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

            // Prepare proposal data
            $proposalData = [
                'customer_id' => $validated['customer_id'],
                'proposal_number' => $proposalNumber,
                'title' => $validated['title'],
                'description' => $validated['description'],
                'items' => json_encode($validated['items']),
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'tax_rate' => $validated['tax_rate'] ?? 0,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'status' => 'draft',
                'valid_until' => $validated['valid_until'],
                'terms_conditions' => $validated['terms_conditions'],
                'notes' => $validated['notes'],
                'created_at' => now()->toISOString(),
                'updated_at' => now()->toISOString()
            ];

            // Create proposal in Supabase
            $result = $this->supabase->insert('proposals', $proposalData);

            if (!$result || (is_array($result) && isset($result['error']))) {
                $errorMessage = is_array($result) && isset($result['error']) ? $result['error']['message'] : 'Failed to create proposal';
                return back()->withInput()->withErrors(['title' => $errorMessage]);
            }

            return redirect()->route('proposals.index')
                ->with('success', 'Proposal created successfully!');

        } catch (\Exception $e) {
            \Log::error('Proposal creation error: ' . $e->getMessage());
            return back()->withInput()->withErrors(['title' => 'Failed to create proposal: ' . $e->getMessage()]);
        }
    }

    /**
     * Display the specified proposal
     */
    public function show($id)
    {
        try {
            $proposals = $this->supabase->query('proposals', '*', ['id' => $id]);
            
            if (empty($proposals) || !is_array($proposals) || count($proposals) === 0) {
                return redirect()->route('proposals.index')
                    ->with('error', 'Proposal not found.');
            }

            $proposal = $proposals[0];

            // Get customer data
            $customers = $this->supabase->query('customers', '*', ['id' => $proposal['customer_id']]);
            $proposal['customer'] = !empty($customers) && is_array($customers) ? $customers[0] : null;

            // Parse items JSON
            $proposal['items'] = json_decode($proposal['items'] ?? '[]', true) ?: [];

            return view('proposals.show', ['proposal' => $proposal]);

        } catch (\Exception $e) {
            \Log::error('Proposal show error: ' . $e->getMessage());
            return redirect()->route('proposals.index')
                ->with('error', 'Failed to load proposal: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified proposal
     */
    public function edit($id)
    {
        try {
            $proposals = $this->supabase->query('proposals', '*', ['id' => $id]);
            
            if (empty($proposals) || !is_array($proposals) || count($proposals) === 0) {
                return redirect()->route('proposals.index')
                    ->with('error', 'Proposal not found.');
            }

            $proposal = $proposals[0];
            $proposal['items'] = json_decode($proposal['items'] ?? '[]', true) ?: [];

            // Get all active customers
            $customers = $this->supabase->query('customers', '*');
            if (!is_array($customers)) {
                $customers = [];
            }
            $customers = array_filter($customers, fn($c) => ($c['status'] ?? 'active') === 'active');

            return view('proposals.edit', [
                'proposal' => $proposal,
                'customers' => $customers
            ]);

        } catch (\Exception $e) {
            \Log::error('Proposal edit error: ' . $e->getMessage());
            return redirect()->route('proposals.index')
                ->with('error', 'Failed to load proposal: ' . $e->getMessage());
        }
    }

    /**
     * Update the specified proposal
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'customer_id' => 'required|integer',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string|max:255',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.rate' => 'required|numeric|min:0',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'discount_amount' => 'nullable|numeric|min:0',
            'valid_until' => 'required|date|after:today',
            'terms_conditions' => 'nullable|string|max:2000',
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            // Calculate totals
            $subtotal = 0;
            foreach ($validated['items'] as $item) {
                $subtotal += $item['quantity'] * $item['rate'];
            }

            $discountAmount = $validated['discount_amount'] ?? 0;
            $taxableAmount = $subtotal - $discountAmount;
            $taxAmount = $taxableAmount * (($validated['tax_rate'] ?? 0) / 100);
            $totalAmount = $taxableAmount + $taxAmount;

            // Update proposal data
            $updateData = [
                'customer_id' => $validated['customer_id'],
                'title' => $validated['title'],
                'description' => $validated['description'],
                'items' => json_encode($validated['items']),
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'tax_rate' => $validated['tax_rate'] ?? 0,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'valid_until' => $validated['valid_until'],
                'terms_conditions' => $validated['terms_conditions'],
                'notes' => $validated['notes'],
                'updated_at' => now()->toISOString()
            ];

            $result = $this->supabase->update('proposals', $id, $updateData);

            if (!$result) {
                return back()->withInput()->withErrors(['title' => 'Failed to update proposal.']);
            }

            return redirect()->route('proposals.index')
                ->with('success', 'Proposal updated successfully!');

        } catch (\Exception $e) {
            \Log::error('Proposal update error: ' . $e->getMessage());
            return back()->withInput()->withErrors(['title' => 'Failed to update proposal: ' . $e->getMessage()]);
        }
    }

    /**
     * Remove the specified proposal (soft delete)
     */
    public function destroy($id)
    {
        try {
            $result = $this->supabase->update('proposals', $id, [
                'deleted_at' => now()->toISOString(),
                'status' => 'cancelled'
            ]);

            if (!$result) {
                return redirect()->route('proposals.index')
                    ->with('error', 'Failed to delete proposal.');
            }

            return redirect()->route('proposals.index')
                ->with('success', 'Proposal deleted successfully!');

        } catch (\Exception $e) {
            \Log::error('Proposal delete error: ' . $e->getMessage());
            return redirect()->route('proposals.index')
                ->with('error', 'Failed to delete proposal: ' . $e->getMessage());
        }
    }

    /**
     * Update proposal status
     */
    public function updateStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'required|in:draft,sent,accepted,rejected,cancelled'
        ]);

        try {
            $result = $this->supabase->update('proposals', $id, [
                'status' => $validated['status'],
                'updated_at' => now()->toISOString()
            ]);

            if (!$result) {
                return response()->json(['error' => 'Failed to update status'], 500);
            }

            return response()->json([
                'success' => true,
                'status' => $validated['status'],
                'message' => "Proposal status changed to {$validated['status']}"
            ]);

        } catch (\Exception $e) {
            \Log::error('Proposal status update error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to update status: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Generate PDF for proposal
     */
    public function generatePdf($id)
    {
        try {
            $proposals = $this->supabase->query('proposals', '*', ['id' => $id]);
            
            if (empty($proposals) || !is_array($proposals) || count($proposals) === 0) {
                return redirect()->route('proposals.index')
                    ->with('error', 'Proposal not found.');
            }

            $proposal = $proposals[0];

            // Get customer data
            $customers = $this->supabase->query('customers', '*', ['id' => $proposal['customer_id']]);
            $proposal['customer'] = !empty($customers) && is_array($customers) ? $customers[0] : null;

            // Parse items JSON
            $proposal['items'] = json_decode($proposal['items'] ?? '[]', true) ?: [];

            $pdf = PDF::loadView('proposals.pdf', ['proposal' => $proposal]);
            
            $filename = 'proposal-' . ($proposal['proposal_number'] ?? $id) . '.pdf';
            
            return $pdf->download($filename);

        } catch (\Exception $e) {
            \Log::error('Proposal PDF generation error: ' . $e->getMessage());
            return redirect()->route('proposals.index')
                ->with('error', 'Failed to generate PDF: ' . $e->getMessage());
        }
    }

    /**
     * Send proposal via email
     */
    public function sendEmail(Request $request, $id)
    {
        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:2000',
            'attach_pdf' => 'boolean'
        ]);

        try {
            $proposals = $this->supabase->query('proposals', '*', ['id' => $id]);
            
            if (empty($proposals) || !is_array($proposals) || count($proposals) === 0) {
                return back()->with('error', 'Proposal not found.');
            }

            $proposal = $proposals[0];

            // Get customer data
            $customers = $this->supabase->query('customers', '*', ['id' => $proposal['customer_id']]);
            $customer = !empty($customers) && is_array($customers) ? $customers[0] : null;

            if (!$customer || !$customer['email']) {
                return back()->with('error', 'Customer email not found.');
            }

            // Parse items for PDF
            $proposal['items'] = json_decode($proposal['items'] ?? '[]', true) ?: [];
            $proposal['customer'] = $customer;

            // Send email
            Mail::send([], [], function ($message) use ($validated, $customer, $proposal) {
                $message->to($customer['email'], $customer['name'])
                        ->subject($validated['subject'])
                        ->html("
                            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;'>
                                <h2 style='color: #4f46e5; text-align: center;'>Proposal from Connectly</h2>
                                <div style='background: #f8fafc; padding: 20px; border-radius: 10px; margin: 20px 0;'>
                                    <p style='font-size: 18px; color: #1f2937;'>Hi {$customer['name']},</p>
                                    <p style='color: #64748b;'>{$validated['message']}</p>
                                    <div style='text-align: center; margin: 30px 0;'>
                                        <a href='" . route('proposals.show', $proposal['id']) . "' style='background: #4f46e5; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; display: inline-block;'>
                                            View Proposal
                                        </a>
                                    </div>
                                </div>
                                <p style='color: #64748b; text-align: center; font-size: 14px;'>
                                    Best regards,<br>The Connectly Team
                                </p>
                            </div>
                        ");

                // Attach PDF if requested
                if ($validated['attach_pdf'] ?? false) {
                    try {
                        $pdf = PDF::loadView('proposals.pdf', ['proposal' => $proposal]);
                        $filename = 'proposal-' . ($proposal['proposal_number'] ?? $proposal['id']) . '.pdf';
                        $message->attachData($pdf->output(), $filename, ['mime' => 'application/pdf']);
                    } catch (\Exception $e) {
                        \Log::error('PDF attachment error: ' . $e->getMessage());
                    }
                }
            });

            // Update proposal status to 'sent'
            $this->supabase->update('proposals', $id, [
                'status' => 'sent',
                'sent_at' => now()->toISOString(),
                'updated_at' => now()->toISOString()
            ]);

            return back()->with('success', 'Proposal sent successfully to ' . $customer['email']);

        } catch (\Exception $e) {
            \Log::error('Proposal email error: ' . $e->getMessage());
            return back()->with('error', 'Failed to send proposal: ' . $e->getMessage());
        }
    }

    /**
     * Convert proposal to invoice
     */
    public function convertToInvoice($id)
    {
        try {
            $proposals = $this->supabase->query('proposals', '*', ['id' => $id]);
            
            if (empty($proposals) || !is_array($proposals) || count($proposals) === 0) {
                return redirect()->route('proposals.index')
                    ->with('error', 'Proposal not found.');
            }

            $proposal = $proposals[0];

            // Generate invoice number
            $invoiceNumber = 'INV-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

            // Create invoice data from proposal
            $invoiceData = [
                'customer_id' => $proposal['customer_id'],
                'proposal_id' => $proposal['id'],
                'invoice_number' => $invoiceNumber,
                'title' => $proposal['title'],
                'description' => $proposal['description'],
                'items' => $proposal['items'],
                'subtotal' => $proposal['subtotal'],
                'discount_amount' => $proposal['discount_amount'],
                'tax_rate' => $proposal['tax_rate'],
                'tax_amount' => $proposal['tax_amount'],
                'total_amount' => $proposal['total_amount'],
                'status' => 'draft',
                'due_date' => date('Y-m-d', strtotime('+30 days')),
                'terms_conditions' => $proposal['terms_conditions'],
                'notes' => $proposal['notes'],
                'created_at' => now()->toISOString(),
                'updated_at' => now()->toISOString()
            ];

            // Create invoice in Supabase
            $result = $this->supabase->insert('invoices', $invoiceData);

            if (!$result || (is_array($result) && isset($result['error']))) {
                return back()->with('error', 'Failed to create invoice from proposal.');
            }

            // Update proposal status to 'accepted'
            $this->supabase->update('proposals', $id, [
                'status' => 'accepted',
                'updated_at' => now()->toISOString()
            ]);

            return redirect()->route('proposals.index')
                ->with('success', 'Proposal converted to invoice successfully!');

        } catch (\Exception $e) {
            \Log::error('Proposal to invoice conversion error: ' . $e->getMessage());
            return back()->with('error', 'Failed to convert proposal to invoice: ' . $e->getMessage());
        }
    }
} 
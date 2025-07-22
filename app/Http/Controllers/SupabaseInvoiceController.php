<?php

namespace App\Http\Controllers;

use App\Services\SupabaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use Stripe\PaymentIntent;
use Barryvdh\DomPDF\Facade\Pdf;

class SupabaseInvoiceController extends Controller
{
    protected $supabase;

    public function __construct(SupabaseService $supabase)
    {
        $this->supabase = $supabase;
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    /**
     * Display a listing of invoices with search and pagination
     */
    public function index(Request $request)
    {
        try {
            // Get all invoices from Supabase
            $invoices = $this->supabase->query('invoices', '*');
            
            if (!is_array($invoices)) {
                $invoices = [];
            }

            // Get customers for linking
            $customers = $this->supabase->query('customers', 'id,name,email,company');
            $customersById = [];
            if (is_array($customers)) {
                foreach ($customers as $customer) {
                    $customersById[$customer['id']] = $customer;
                }
            }

            // Add customer data to invoices
            foreach ($invoices as &$invoice) {
                $invoice['customer'] = $customersById[$invoice['customer_id']] ?? null;
            }

            // Filter by search if provided
            if ($request->filled('search')) {
                $search = strtolower($request->search);
                $invoices = array_filter($invoices, function($invoice) use ($search) {
                    return str_contains(strtolower($invoice['title'] ?? ''), $search) ||
                           str_contains(strtolower($invoice['invoice_number'] ?? ''), $search) ||
                           str_contains(strtolower($invoice['customer']['name'] ?? ''), $search) ||
                           str_contains(strtolower($invoice['customer']['company'] ?? ''), $search);
                });
            }

            // Filter by status if provided
            if ($request->filled('status')) {
                $invoices = array_filter($invoices, function($invoice) use ($request) {
                    return ($invoice['status'] ?? 'draft') === $request->status;
                });
            }

            // Filter by customer if provided
            if ($request->filled('customer_id')) {
                $invoices = array_filter($invoices, function($invoice) use ($request) {
                    return $invoice['customer_id'] == $request->customer_id;
                });
            }

            // Sort invoices by created_at (newest first)
            usort($invoices, function($a, $b) {
                return strtotime($b['created_at'] ?? '0') - strtotime($a['created_at'] ?? '0');
            });

            // Simple pagination
            $perPage = 10;
            $page = $request->get('page', 1);
            $offset = ($page - 1) * $perPage;
            $paginatedInvoices = array_slice($invoices, $offset, $perPage);
            $totalPages = ceil(count($invoices) / $perPage);

            // Calculate statistics
            $stats = [
                'total' => count($invoices),
                'draft' => count(array_filter($invoices, fn($i) => ($i['status'] ?? 'draft') === 'draft')),
                'sent' => count(array_filter($invoices, fn($i) => ($i['status'] ?? 'draft') === 'sent')),
                'paid' => count(array_filter($invoices, fn($i) => ($i['status'] ?? 'draft') === 'paid')),
                'overdue' => count(array_filter($invoices, function($i) {
                    return ($i['status'] ?? 'draft') === 'sent' && 
                           isset($i['due_date']) && 
                           strtotime($i['due_date']) < time();
                })),
                'total_revenue' => array_sum(array_column(array_filter($invoices, fn($i) => ($i['status'] ?? 'draft') === 'paid'), 'total_amount')),
                'outstanding' => array_sum(array_column(array_filter($invoices, fn($i) => ($i['status'] ?? 'draft') === 'sent'), 'total_amount'))
            ];

            return view('invoices.index', [
                'invoices' => $paginatedInvoices,
                'customers' => $customers ?? [],
                'stats' => $stats,
                'currentPage' => $page,
                'totalPages' => $totalPages,
                'search' => $request->search,
                'status' => $request->status,
                'customer_id' => $request->customer_id
            ]);

        } catch (\Exception $e) {
            \Log::error('Invoice index error: ' . $e->getMessage());
            return view('invoices.index', [
                'invoices' => [],
                'customers' => [],
                'stats' => ['total' => 0, 'draft' => 0, 'sent' => 0, 'paid' => 0, 'overdue' => 0, 'total_revenue' => 0, 'outstanding' => 0],
                'error' => 'Failed to load invoices: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Show the form for creating a new invoice
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
            $proposalData = null;

            // If creating from proposal
            if ($request->filled('proposal_id')) {
                $proposals = $this->supabase->query('proposals', '*', ['id' => $request->proposal_id]);
                if (!empty($proposals) && is_array($proposals)) {
                    $proposalData = $proposals[0];
                    $proposalData['items'] = json_decode($proposalData['items'] ?? '[]', true) ?: [];
                    $preselectedCustomer = $proposalData['customer_id'];
                }
            }

            if ($request->filled('customer_id')) {
                $preselectedCustomer = $request->customer_id;
            }

            return view('invoices.create', [
                'customers' => $customers,
                'preselectedCustomer' => $preselectedCustomer,
                'proposalData' => $proposalData
            ]);

        } catch (\Exception $e) {
            \Log::error('Invoice create form error: ' . $e->getMessage());
            return redirect()->route('invoices.index')
                ->with('error', 'Failed to load invoice creation form: ' . $e->getMessage());
        }
    }

    /**
     * Store a newly created invoice
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|integer',
            'proposal_id' => 'nullable|integer',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string|max:255',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.rate' => 'required|numeric|min:0',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'discount_amount' => 'nullable|numeric|min:0',
            'due_date' => 'required|date|after:today',
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

            // Generate invoice number
            $invoiceNumber = 'INV-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

            // Prepare invoice data
            $invoiceData = [
                'customer_id' => $validated['customer_id'],
                'proposal_id' => $validated['proposal_id'],
                'invoice_number' => $invoiceNumber,
                'title' => $validated['title'],
                'description' => $validated['description'],
                'items' => json_encode($validated['items']),
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'tax_rate' => $validated['tax_rate'] ?? 0,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'status' => 'draft',
                'due_date' => $validated['due_date'],
                'terms_conditions' => $validated['terms_conditions'],
                'notes' => $validated['notes'],
                'created_at' => now()->toISOString(),
                'updated_at' => now()->toISOString()
            ];

            // Create invoice in Supabase
            $result = $this->supabase->insert('invoices', $invoiceData);

            if (!$result || (is_array($result) && isset($result['error']))) {
                $errorMessage = is_array($result) && isset($result['error']) ? $result['error']['message'] : 'Failed to create invoice';
                return back()->withInput()->withErrors(['title' => $errorMessage]);
            }

            return redirect()->route('invoices.index')
                ->with('success', 'Invoice created successfully!');

        } catch (\Exception $e) {
            \Log::error('Invoice creation error: ' . $e->getMessage());
            return back()->withInput()->withErrors(['title' => 'Failed to create invoice: ' . $e->getMessage()]);
        }
    }

    /**
     * Display the specified invoice
     */
    public function show($id)
    {
        try {
            $invoices = $this->supabase->query('invoices', '*', ['id' => $id]);
            
            if (empty($invoices) || !is_array($invoices) || count($invoices) === 0) {
                return redirect()->route('invoices.index')
                    ->with('error', 'Invoice not found.');
            }

            $invoice = $invoices[0];

            // Get customer data
            $customers = $this->supabase->query('customers', '*', ['id' => $invoice['customer_id']]);
            $invoice['customer'] = !empty($customers) && is_array($customers) ? $customers[0] : null;

            // Parse items JSON
            $invoice['items'] = json_decode($invoice['items'] ?? '[]', true) ?: [];

            // Get related transactions
            $transactions = $this->supabase->query('transactions', '*', ['invoice_id' => $id]);
            $invoice['transactions'] = is_array($transactions) ? $transactions : [];

            return view('invoices.show', ['invoice' => $invoice]);

        } catch (\Exception $e) {
            \Log::error('Invoice show error: ' . $e->getMessage());
            return redirect()->route('invoices.index')
                ->with('error', 'Failed to load invoice: ' . $e->getMessage());
        }
    }

    /**
     * Update invoice status
     */
    public function updateStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'required|in:draft,sent,paid,cancelled,overdue'
        ]);

        try {
            $updateData = [
                'status' => $validated['status'],
                'updated_at' => now()->toISOString()
            ];

            if ($validated['status'] === 'sent') {
                $updateData['sent_at'] = now()->toISOString();
            } elseif ($validated['status'] === 'paid') {
                $updateData['paid_at'] = now()->toISOString();
            }

            $result = $this->supabase->update('invoices', $id, $updateData);

            if (!$result) {
                return response()->json(['error' => 'Failed to update status'], 500);
            }

            return response()->json([
                'success' => true,
                'status' => $validated['status'],
                'message' => "Invoice status changed to {$validated['status']}"
            ]);

        } catch (\Exception $e) {
            \Log::error('Invoice status update error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to update status: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Create Stripe checkout session for invoice payment
     */
    public function createPaymentSession($id)
    {
        try {
            $invoices = $this->supabase->query('invoices', '*', ['id' => $id]);
            
            if (empty($invoices) || !is_array($invoices) || count($invoices) === 0) {
                return redirect()->route('invoices.index')
                    ->with('error', 'Invoice not found.');
            }

            $invoice = $invoices[0];

            // Get customer data
            $customers = $this->supabase->query('customers', '*', ['id' => $invoice['customer_id']]);
            $customer = !empty($customers) && is_array($customers) ? $customers[0] : null;

            if (!$customer) {
                return redirect()->route('invoices.show', $id)
                    ->with('error', 'Customer not found for this invoice.');
            }

            // Parse items for line items
            $items = json_decode($invoice['items'] ?? '[]', true) ?: [];
            $lineItems = [];

            foreach ($items as $item) {
                $lineItems[] = [
                    'price_data' => [
                        'currency' => 'usd',
                        'product_data' => [
                            'name' => $item['description'],
                        ],
                        'unit_amount' => (int) ($item['rate'] * 100), // Convert to cents
                    ],
                    'quantity' => (int) $item['quantity'],
                ];
            }

            // Add discount if applicable
            $discounts = [];
            if ($invoice['discount_amount'] > 0) {
                // Create a coupon for the discount
                $coupon = \Stripe\Coupon::create([
                    'amount_off' => (int) ($invoice['discount_amount'] * 100),
                    'currency' => 'usd',
                    'duration' => 'once',
                    'name' => 'Invoice Discount',
                ]);
                $discounts[] = ['coupon' => $coupon->id];
            }

            // Create checkout session
            $session = Session::create([
                'payment_method_types' => ['card'],
                'line_items' => $lineItems,
                'discounts' => $discounts,
                'mode' => 'payment',
                'customer_email' => $customer['email'],
                'success_url' => route('invoices.payment.success', $id) . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('invoices.show', $id),
                'metadata' => [
                    'invoice_id' => $id,
                    'invoice_number' => $invoice['invoice_number'],
                    'customer_id' => $invoice['customer_id'],
                ],
                'automatic_tax' => [
                    'enabled' => $invoice['tax_rate'] > 0,
                ],
            ]);

            // Store session ID in invoice
            $this->supabase->update('invoices', $id, [
                'stripe_session_id' => $session->id,
                'updated_at' => now()->toISOString()
            ]);

            return redirect($session->url);

        } catch (\Exception $e) {
            \Log::error('Stripe session creation error: ' . $e->getMessage());
            return redirect()->route('invoices.show', $id)
                ->with('error', 'Failed to create payment session: ' . $e->getMessage());
        }
    }

    /**
     * Handle successful payment
     */
    public function paymentSuccess(Request $request, $id)
    {
        try {
            $sessionId = $request->get('session_id');
            
            if (!$sessionId) {
                return redirect()->route('invoices.show', $id)
                    ->with('error', 'Invalid payment session.');
            }

            // Retrieve the session from Stripe
            $session = Session::retrieve($sessionId);

            if ($session->payment_status === 'paid') {
                // Update invoice status to paid
                $this->supabase->update('invoices', $id, [
                    'status' => 'paid',
                    'paid_at' => now()->toISOString(),
                    'updated_at' => now()->toISOString()
                ]);

                // Create transaction record
                $transactionData = [
                    'invoice_id' => $id,
                    'customer_id' => $session->metadata->customer_id,
                    'stripe_session_id' => $sessionId,
                    'stripe_payment_intent_id' => $session->payment_intent,
                    'amount' => $session->amount_total / 100, // Convert from cents
                    'currency' => $session->currency,
                    'status' => 'completed',
                    'payment_method' => 'card',
                    'created_at' => now()->toISOString(),
                    'updated_at' => now()->toISOString()
                ];

                $this->supabase->insert('transactions', $transactionData);

                // Send payment confirmation email
                $this->sendPaymentConfirmationEmail($id);

                return view('invoices.payment-success', [
                    'invoice_id' => $id,
                    'amount' => $session->amount_total / 100,
                    'session' => $session
                ]);
            } else {
                return redirect()->route('invoices.show', $id)
                    ->with('error', 'Payment was not successful.');
            }

        } catch (\Exception $e) {
            \Log::error('Payment success handling error: ' . $e->getMessage());
            return redirect()->route('invoices.show', $id)
                ->with('error', 'Failed to process payment confirmation: ' . $e->getMessage());
        }
    }

    /**
     * Send invoice via email
     */
    public function sendEmail(Request $request, $id)
    {
        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:2000',
            'attach_pdf' => 'boolean'
        ]);

        try {
            $invoices = $this->supabase->query('invoices', '*', ['id' => $id]);
            
            if (empty($invoices) || !is_array($invoices) || count($invoices) === 0) {
                return back()->with('error', 'Invoice not found.');
            }

            $invoice = $invoices[0];

            // Get customer data
            $customers = $this->supabase->query('customers', '*', ['id' => $invoice['customer_id']]);
            $customer = !empty($customers) && is_array($customers) ? $customers[0] : null;

            if (!$customer || !$customer['email']) {
                return back()->with('error', 'Customer email not found.');
            }

            // Parse items for PDF
            $invoice['items'] = json_decode($invoice['items'] ?? '[]', true) ?: [];
            $invoice['customer'] = $customer;

            // Create payment link
            $paymentLink = route('invoices.payment', $id);

            // Send email
            Mail::send([], [], function ($message) use ($validated, $customer, $invoice, $paymentLink) {
                $message->to($customer['email'], $customer['name'])
                        ->subject($validated['subject'])
                        ->html("
                            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;'>
                                <h2 style='color: #4f46e5; text-align: center;'>Invoice from Connectly</h2>
                                <div style='background: #f8fafc; padding: 20px; border-radius: 10px; margin: 20px 0;'>
                                    <p style='font-size: 18px; color: #1f2937;'>Hi {$customer['name']},</p>
                                    <p style='color: #64748b;'>{$validated['message']}</p>
                                    <div style='text-align: center; margin: 30px 0;'>
                                        <a href='{$paymentLink}' style='background: #10b981; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; display: inline-block; margin-right: 10px;'>
                                            Pay Now (\${$invoice['total_amount']})
                                        </a>
                                        <a href='" . route('invoices.show', $invoice['id']) . "' style='background: #4f46e5; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; display: inline-block;'>
                                            View Invoice
                                        </a>
                                    </div>
                                    <p style='color: #64748b; font-size: 14px;'>
                                        Invoice #{$invoice['invoice_number']}<br>
                                        Amount: \${$invoice['total_amount']}<br>
                                        Due Date: " . date('M j, Y', strtotime($invoice['due_date'])) . "
                                    </p>
                                </div>
                                <p style='color: #64748b; text-align: center; font-size: 14px;'>
                                    Best regards,<br>The Connectly Team
                                </p>
                            </div>
                        ");

                // Attach PDF if requested
                if ($validated['attach_pdf'] ?? false) {
                    try {
                        $pdf = PDF::loadView('invoices.pdf', ['invoice' => $invoice]);
                        $filename = 'invoice-' . ($invoice['invoice_number'] ?? $invoice['id']) . '.pdf';
                        $message->attachData($pdf->output(), $filename, ['mime' => 'application/pdf']);
                    } catch (\Exception $e) {
                        \Log::error('PDF attachment error: ' . $e->getMessage());
                    }
                }
            });

            // Update invoice status to 'sent'
            $this->supabase->update('invoices', $id, [
                'status' => 'sent',
                'sent_at' => now()->toISOString(),
                'updated_at' => now()->toISOString()
            ]);

            return back()->with('success', 'Invoice sent successfully to ' . $customer['email']);

        } catch (\Exception $e) {
            \Log::error('Invoice email error: ' . $e->getMessage());
            return back()->with('error', 'Failed to send invoice: ' . $e->getMessage());
        }
    }

    /**
     * Send payment confirmation email
     */
    private function sendPaymentConfirmationEmail($invoiceId)
    {
        try {
            $invoices = $this->supabase->query('invoices', '*', ['id' => $invoiceId]);
            if (empty($invoices)) return;

            $invoice = $invoices[0];
            $customers = $this->supabase->query('customers', '*', ['id' => $invoice['customer_id']]);
            if (empty($customers)) return;

            $customer = $customers[0];

            Mail::send([], [], function ($message) use ($customer, $invoice) {
                $message->to($customer['email'], $customer['name'])
                        ->subject('Payment Confirmation - Invoice #' . $invoice['invoice_number'])
                        ->html("
                            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;'>
                                <h2 style='color: #10b981; text-align: center;'>Payment Received!</h2>
                                <div style='background: #f0fdf4; padding: 20px; border-radius: 10px; margin: 20px 0; border: 1px solid #bbf7d0;'>
                                    <p style='font-size: 18px; color: #1f2937;'>Hi {$customer['name']},</p>
                                    <p style='color: #064e3b;'>Thank you! We have received your payment for invoice #{$invoice['invoice_number']}.</p>
                                    <div style='background: white; padding: 15px; border-radius: 8px; margin: 15px 0;'>
                                        <p style='color: #064e3b; margin: 0;'><strong>Payment Details:</strong></p>
                                        <p style='color: #064e3b; margin: 5px 0;'>Amount: \${$invoice['total_amount']}</p>
                                        <p style='color: #064e3b; margin: 5px 0;'>Date: " . date('M j, Y g:i A') . "</p>
                                        <p style='color: #064e3b; margin: 5px 0;'>Status: Paid</p>
                                    </div>
                                    <p style='color: #064e3b;'>A receipt has been sent to your email address.</p>
                                </div>
                                <p style='color: #64748b; text-align: center; font-size: 14px;'>
                                    Thank you for your business!<br>The Connectly Team
                                </p>
                            </div>
                        ");
            });

        } catch (\Exception $e) {
            \Log::error('Payment confirmation email error: ' . $e->getMessage());
        }
    }

    /**
     * Generate PDF for invoice
     */
    public function generatePdf($id)
    {
        try {
            $invoices = $this->supabase->query('invoices', '*', ['id' => $id]);
            
            if (empty($invoices) || !is_array($invoices) || count($invoices) === 0) {
                return redirect()->route('invoices.index')
                    ->with('error', 'Invoice not found.');
            }

            $invoice = $invoices[0];

            // Get customer data
            $customers = $this->supabase->query('customers', '*', ['id' => $invoice['customer_id']]);
            $invoice['customer'] = !empty($customers) && is_array($customers) ? $customers[0] : null;

            // Parse items JSON
            $invoice['items'] = json_decode($invoice['items'] ?? '[]', true) ?: [];

            $pdf = PDF::loadView('invoices.pdf', ['invoice' => $invoice]);
            
            $filename = 'invoice-' . ($invoice['invoice_number'] ?? $id) . '.pdf';
            
            return $pdf->download($filename);

        } catch (\Exception $e) {
            \Log::error('Invoice PDF generation error: ' . $e->getMessage());
            return redirect()->route('invoices.index')
                ->with('error', 'Failed to generate PDF: ' . $e->getMessage());
        }
    }
} 
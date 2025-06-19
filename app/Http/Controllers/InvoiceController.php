<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Customer;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\InvoiceCreated;
use App\Mail\InvoicePaymentMail;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Stripe\Checkout\Session;

class InvoiceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $invoices = \App\Models\Invoice::with('customer')->orderByDesc('created_at')->paginate(10);
        return view('invoices.index', compact('invoices'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $customers = \App\Models\Customer::all();
        return view('invoices.create', compact('customers'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'amount' => 'required|numeric|min:0',
            'tax_amount' => 'required|numeric|min:0',
            'issue_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:issue_date',
            'notes' => 'nullable|string',
        ]);

        $invoice = new \App\Models\Invoice();
        $invoice->customer_id = $validated['customer_id'];
        $invoice->amount = $validated['amount'];
        $invoice->tax_amount = $validated['tax_amount'];
        $invoice->issue_date = $validated['issue_date'];
        $invoice->due_date = $validated['due_date'];
        $invoice->notes = $validated['notes'] ?? null;
        $invoice->status = 'draft';
        $invoice->total_amount = $invoice->amount + $invoice->tax_amount;

        // Generate invoice number
        $lastInvoice = \App\Models\Invoice::orderBy('id', 'desc')->first();
        $nextId = $lastInvoice ? $lastInvoice->id + 1 : 1;
        $invoice->invoice_number = 'INV-' . date('Y') . '-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);

        $invoice->save();

        return redirect()->route('invoices.index')->with('success', 'Invoice created successfully!');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $invoice = \App\Models\Invoice::with(['customer', 'transactions'])->findOrFail($id);
        return view('invoices.show', compact('invoice'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $invoice = \App\Models\Invoice::findOrFail($id);
        $customers = \App\Models\Customer::all();
        return view('invoices.edit', compact('invoice', 'customers'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $invoice = \App\Models\Invoice::findOrFail($id);
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'amount' => 'required|numeric|min:0',
            'tax_amount' => 'required|numeric|min:0',
            'issue_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:issue_date',
            'notes' => 'nullable|string',
        ]);
        $invoice->customer_id = $validated['customer_id'];
        $invoice->amount = $validated['amount'];
        $invoice->tax_amount = $validated['tax_amount'];
        $invoice->issue_date = $validated['issue_date'];
        $invoice->due_date = $validated['due_date'];
        $invoice->notes = $validated['notes'] ?? null;
        $invoice->total_amount = $invoice->amount + $invoice->tax_amount;
        $invoice->save();
        return redirect()->route('invoices.index')->with('success', 'Invoice updated successfully!');
    }

    /**
     * Send invoice payment email to customer
     */
    public function send(Invoice $invoice)
    {
        // Send payment email to customer
        Mail::to($invoice->customer->email)->send(new InvoicePaymentMail($invoice));
        
        // Update invoice status to sent
        $invoice->update(['status' => 'sent']);
        
        return redirect()->route('invoices.show', $invoice)->with('success', 'Payment email sent successfully!');
    }

    /**
     * Show payment page for invoice
     */
    public function payment(Request $request, Invoice $invoice)
    {
        // Handle cancelled payment
        if ($request->get('cancelled') === 'true') {
            // Find and update any pending transactions for this invoice to cancelled
            $pendingTransactions = Transaction::where('invoice_id', $invoice->id)
                ->where('status', 'pending')
                ->get();
                
            foreach ($pendingTransactions as $transaction) {
                $transaction->update(['status' => 'cancelled']);
                \Log::info('Transaction marked as cancelled', [
                    'transaction_id' => $transaction->id,
                    'transaction_number' => $transaction->transaction_number,
                    'invoice_number' => $invoice->invoice_number
                ]);
            }
            
            return view('invoices.payment', compact('invoice'))
                ->with('info', 'Payment was cancelled. You can try again below.');
        }
        
        return view('invoices.payment', compact('invoice'));
    }

    /**
     * Create Stripe Checkout session and redirect
     */
    public function redirectToStripe(Invoice $invoice)
    {
        // Check if Stripe keys are configured
        $stripeSecret = config('services.stripe.secret');
        if (empty($stripeSecret)) {
            return redirect()->route('invoices.payment', $invoice)->with('error', 'Stripe is not configured. Please check your environment variables.');
        }
        
        // Set your Stripe secret key
        Stripe::setApiKey($stripeSecret);
        
        try {
            $session = Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'usd',
                        'product_data' => [
                            'name' => 'Invoice #' . $invoice->invoice_number,
                            'description' => 'Payment for invoice issued on ' . $invoice->issue_date->format('F j, Y'),
                        ],
                        'unit_amount' => (int)($invoice->total_amount * 100), // Convert to cents
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => route('invoices.payment.success', $invoice) . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('invoices.payment', $invoice) . '?cancelled=true',
                'expires_at' => time() + (30 * 60), // Expire after 30 minutes
                'metadata' => [
                    'invoice_id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                ],
            ]);
            
            // Create a pending transaction when checkout session is created
            $this->createPendingTransaction($invoice, $session->id);
            
            return redirect($session->url);
        } catch (\Exception $e) {
            \Log::error('Stripe payment failed: ' . $e->getMessage());
            return redirect()->route('invoices.payment', $invoice)->with('error', 'Payment setup failed: ' . $e->getMessage());
        }
    }

    /**
     * Create a pending transaction when checkout session is initiated
     */
    private function createPendingTransaction(Invoice $invoice, $sessionId)
    {
        // Check if transaction already exists for this session
        $existingTransaction = Transaction::where('stripe_session_id', $sessionId)->first();
        
        if ($existingTransaction) {
            return $existingTransaction;
        }

        $transaction = new Transaction();
        $transaction->customer_id = $invoice->customer_id;
        $transaction->invoice_id = $invoice->id;
        $transaction->transaction_number = 'TXN-' . time();
        $transaction->amount = $invoice->total_amount;
        $transaction->payment_method = 'stripe';
        $transaction->status = 'pending';
        $transaction->stripe_session_id = $sessionId;
        $transaction->save();

        \Log::info('Pending transaction created for checkout session', [
            'transaction_id' => $transaction->id,
            'transaction_number' => $transaction->transaction_number,
            'invoice_number' => $invoice->invoice_number,
            'session_id' => $sessionId
        ]);

        return $transaction;
    }

    /**
     * Handle successful payment
     */
    public function paymentSuccess(Request $request, Invoice $invoice)
    {
        $sessionId = $request->get('session_id');
        
        if ($sessionId) {
            // Auto-sync transaction status when user returns from payment
            $this->syncTransactionStatus($sessionId, $invoice);
            
            // Find the transaction (should be updated now)
            $transaction = Transaction::where('stripe_session_id', $sessionId)->first();
            
            if ($transaction) {
                return view('invoices.payment-success', compact('invoice', 'transaction'));
            }
            
            // Show success page even if transaction not found yet
            return view('invoices.payment-success', compact('invoice'));
        }
        
        return redirect()->route('invoices.show', $invoice);
    }

    /**
     * Auto-sync transaction status when user returns from Stripe
     */
    private function syncTransactionStatus($sessionId, $invoice)
    {
        try {
            \Stripe\Stripe::setApiKey(config('services.stripe.secret'));
            
            // Retrieve the checkout session from Stripe
            $session = \Stripe\Checkout\Session::retrieve($sessionId);
            
            // Find or create transaction
            $transaction = Transaction::where('stripe_session_id', $sessionId)->first();
            
            if (!$transaction) {
                // Create transaction if it doesn't exist
                $transaction = $this->createPendingTransaction($invoice, $sessionId);
            }
            
            // Determine the correct status based on Stripe session
            $newStatus = 'pending';
            $invoiceStatus = 'sent';
            
            if ($session->status === 'complete') {
                if ($session->payment_status === 'paid') {
                    $newStatus = 'completed';
                    $invoiceStatus = 'paid';
                } else {
                    $newStatus = 'failed';
                    $invoiceStatus = 'sent';
                }
            } elseif ($session->status === 'expired') {
                $newStatus = 'expired';
                $invoiceStatus = 'sent';
            }
            
            // Update transaction status
            if ($transaction->status !== $newStatus) {
                $oldStatus = $transaction->status;
                $transaction->update(['status' => $newStatus]);
                
                \Log::info('Transaction status auto-synced on return from payment', [
                    'transaction_id' => $transaction->id,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                    'stripe_status' => $session->status,
                    'payment_status' => $session->payment_status ?? 'unknown'
                ]);
            }
            
            // Update invoice status
            $updateData = ['status' => $invoiceStatus];
            if ($invoiceStatus === 'paid') {
                $updateData['paid_at'] = now();
            }
            $invoice->update($updateData);
            
        } catch (\Exception $e) {
            \Log::error('Failed to auto-sync transaction status: ' . $e->getMessage(), [
                'session_id' => $sessionId,
                'invoice_id' => $invoice->id
            ]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $invoice = \App\Models\Invoice::findOrFail($id);
        
        // Only allow deletion of draft invoices
        if ($invoice->status !== 'draft') {
            return redirect()->route('invoices.index')->with('error', 'Only draft invoices can be deleted.');
        }
        
        $invoice->delete();
        
        return redirect()->route('invoices.index')->with('success', 'Invoice deleted successfully!');
    }

    /**
     * Check payment status for AJAX calls from payment success page
     */
    public function checkPaymentStatus(Request $request, Invoice $invoice)
    {
        $sessionId = $request->get('session_id');
        
        if (!$sessionId) {
            return response()->json(['success' => false, 'error' => 'No session ID provided']);
        }

        try {
            // Auto-sync the transaction status
            $this->syncTransactionStatus($sessionId, $invoice);
            
            // Find the updated transaction
            $transaction = Transaction::where('stripe_session_id', $sessionId)->first();
            
            if ($transaction) {
                return response()->json([
                    'success' => true,
                    'transaction' => [
                        'id' => $transaction->id,
                        'transaction_number' => $transaction->transaction_number,
                        'status' => $transaction->status,
                        'amount' => $transaction->amount,
                        'updated_at' => $transaction->updated_at->format('M d, Y H:i:s'),
                    ]
                ]);
            }
            
            return response()->json(['success' => false, 'error' => 'Transaction not found']);
            
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Change invoice status
     */
    public function changeStatus(Request $request, Invoice $invoice)
    {
        $validated = $request->validate([
            'status' => 'required|in:draft,sent,paid,overdue,cancelled'
        ]);
        
        $invoice->update(['status' => $validated['status']]);
        
        return redirect()->route('invoices.index')->with('success', 'Invoice status updated successfully!');
    }
}

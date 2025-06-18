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
    public function payment(Invoice $invoice)
    {
        return view('invoices.payment', compact('invoice'));
    }

    /**
     * Create Stripe Checkout session and redirect
     */
    public function redirectToStripe(Invoice $invoice)
    {
        // Set your Stripe secret key
        Stripe::setApiKey(config('services.stripe.secret'));
        
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
                'cancel_url' => route('invoices.show', $invoice),
                'metadata' => [
                    'invoice_id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                ],
            ]);
            
            return redirect($session->url);
        } catch (\Exception $e) {
            return redirect()->route('invoices.show', $invoice)->with('error', 'Payment setup failed: ' . $e->getMessage());
        }
    }

    /**
     * Handle successful payment
     */
    public function paymentSuccess(Request $request, Invoice $invoice)
    {
        $sessionId = $request->get('session_id');
        
        if ($sessionId) {
            Stripe::setApiKey(config('services.stripe.secret'));
            
            try {
                $session = Session::retrieve($sessionId);
                
                if ($session->payment_status === 'paid') {
                    // Create transaction record
                    $transaction = new Transaction();
                    $transaction->invoice_id = $invoice->id;
                    $transaction->transaction_number = 'TXN-' . time();
                    $transaction->amount = $invoice->total_amount;
                    $transaction->payment_method = 'stripe';
                    $transaction->status = 'completed';
                    $transaction->stripe_session_id = $sessionId;
                    $transaction->save();
                    
                    // Update invoice status
                    $invoice->update(['status' => 'paid']);
                    
                    return view('invoices.payment-success', compact('invoice', 'transaction'));
                }
            } catch (\Exception $e) {
                return redirect()->route('invoices.show', $invoice)->with('error', 'Payment verification failed');
            }
        }
        
        return redirect()->route('invoices.show', $invoice);
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

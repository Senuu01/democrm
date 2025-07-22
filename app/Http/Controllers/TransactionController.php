<?php

namespace App\Http\Controllers;

use App\Services\SupabaseService;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    protected $supabase;

    public function __construct(SupabaseService $supabase)
    {
        $this->supabase = $supabase;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            // Get all transactions from Supabase
            $transactions = $this->supabase->query('transactions', '*');
            
            if (!is_array($transactions)) {
                $transactions = [];
            }

            // Get customers and invoices for linking
            $customers = $this->supabase->query('customers', 'id,name,email,company');
            $customersById = [];
            if (is_array($customers)) {
                foreach ($customers as $customer) {
                    $customersById[$customer['id']] = $customer;
                }
            }

            $invoices = $this->supabase->query('invoices', 'id,invoice_number,title,amount');
            $invoicesById = [];
            if (is_array($invoices)) {
                foreach ($invoices as $invoice) {
                    $invoicesById[$invoice['id']] = $invoice;
                }
            }

            // Add customer and invoice data to transactions
            foreach ($transactions as &$transaction) {
                $transaction['customer'] = $customersById[$transaction['customer_id']] ?? null;
                $transaction['invoice'] = $invoicesById[$transaction['invoice_id']] ?? null;
            }

            // Sort by created_at desc (latest first)
            usort($transactions, function($a, $b) {
                return strtotime($b['created_at'] ?? '1970-01-01') - strtotime($a['created_at'] ?? '1970-01-01');
            });

            return view('transactions.index', compact('transactions'));
            
        } catch (\Exception $e) {
            \Log::error('Transaction index error: ' . $e->getMessage());
            return view('transactions.index', ['transactions' => []]);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        try {
            // Get transaction from Supabase
            $transactions = $this->supabase->query('transactions', '*', ['id' => $id]);
            
            if (empty($transactions) || !is_array($transactions)) {
                return redirect()->route('transactions.index')
                    ->with('error', 'Transaction not found.');
            }

            $transaction = $transactions[0];

            // Get related customer
            if (isset($transaction['customer_id'])) {
                $customers = $this->supabase->query('customers', '*', ['id' => $transaction['customer_id']]);
                $transaction['customer'] = !empty($customers) ? $customers[0] : null;
            }

            // Get related invoice
            if (isset($transaction['invoice_id'])) {
                $invoices = $this->supabase->query('invoices', '*', ['id' => $transaction['invoice_id']]);
                $transaction['invoice'] = !empty($invoices) ? $invoices[0] : null;
            }

            return view('transactions.show', compact('transaction'));
            
        } catch (\Exception $e) {
            \Log::error('Transaction show error: ' . $e->getMessage());
            return redirect()->route('transactions.index')
                ->with('error', 'Failed to load transaction.');
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function updateStatus(Request $request, Transaction $transaction)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,completed,failed,refunded,expired,cancelled',
        ]);

        $transaction->update($validated);

        // If transaction is completed, update invoice status
        if ($validated['status'] === 'completed' && $transaction->invoice) {
            $transaction->invoice->update(['status' => 'paid', 'paid_at' => now()]);
        }

        // If transaction is refunded, update invoice status
        if ($validated['status'] === 'refunded' && $transaction->invoice) {
            $transaction->invoice->update(['status' => 'refunded']);
        }

        // If transaction failed or expired, keep invoice as sent for retry
        if (in_array($validated['status'], ['failed', 'expired']) && $transaction->invoice) {
            $transaction->invoice->update(['status' => 'sent']);
        }

        return redirect()->route('transactions.index')
            ->with('success', 'Transaction status updated successfully.');
    }

    public function refund(Transaction $transaction)
    {
        if ($transaction->status !== 'completed') {
            return redirect()->route('transactions.index')
                ->with('error', 'Only completed transactions can be refunded.');
        }

        try {
            // Process refund through Stripe
            \Stripe\Stripe::setApiKey(config('services.stripe.secret'));
            $refund = \Stripe\Refund::create([
                'payment_intent' => $transaction->stripe_payment_intent_id,
            ]);

            // Update transaction status
            $transaction->update([
                'status' => 'refunded',
                'refund_id' => $refund->id,
            ]);

            // Update invoice status
            if ($transaction->invoice) {
                $transaction->invoice->update(['status' => 'refunded']);
            }

            return redirect()->route('transactions.index')
                ->with('success', 'Transaction refunded successfully.');
        } catch (\Exception $e) {
            return redirect()->route('transactions.index')
                ->with('error', 'Failed to process refund: ' . $e->getMessage());
        }
    }

    /**
     * Sync transaction status with Stripe
     */
    public function syncWithStripe(Transaction $transaction)
    {
        try {
            \Stripe\Stripe::setApiKey(config('services.stripe.secret'));
            
            $stripeStatus = null;
            $statusMessage = '';
            
            // Check Stripe session if available
            if ($transaction->stripe_session_id) {
                $session = \Stripe\Checkout\Session::retrieve($transaction->stripe_session_id);
                $stripeStatus = $this->mapStripeSessionStatus($session);
                $statusMessage = $this->getStripeStatusMessage($session);
                
                // Update transaction based on Stripe session status
                $this->updateTransactionFromStripeSession($transaction, $session);
            }
            // Check Stripe payment intent if available
            elseif ($transaction->stripe_payment_intent_id) {
                $paymentIntent = \Stripe\PaymentIntent::retrieve($transaction->stripe_payment_intent_id);
                $stripeStatus = $this->mapStripePaymentIntentStatus($paymentIntent);
                $statusMessage = $this->getStripePaymentIntentMessage($paymentIntent);
                
                // Update transaction based on payment intent status
                $this->updateTransactionFromPaymentIntent($transaction, $paymentIntent);
            }

            return redirect()->back()
                ->with('success', 'Transaction status synced with Stripe: ' . $statusMessage);
                
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to sync with Stripe: ' . $e->getMessage());
        }
    }

    /**
     * Sync all pending transactions with Stripe
     */
    public function syncAllPending()
    {
        try {
            $pendingTransactions = Transaction::where('status', 'pending')
                ->whereNotNull('stripe_session_id')
                ->orWhere(function($query) {
                    $query->where('status', 'pending')->whereNotNull('stripe_payment_intent_id');
                })
                ->get();

            $syncedCount = 0;
            $errorCount = 0;

            foreach ($pendingTransactions as $transaction) {
                try {
                    $this->performStripeSync($transaction);
                    $syncedCount++;
                } catch (\Exception $e) {
                    $errorCount++;
                    \Log::error('Failed to sync transaction ' . $transaction->id . ': ' . $e->getMessage());
                }
            }

            $message = "Synced {$syncedCount} transactions successfully.";
            if ($errorCount > 0) {
                $message .= " {$errorCount} failed to sync.";
            }

            return redirect()->back()->with('success', $message);
            
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to sync transactions: ' . $e->getMessage());
        }
    }

    /**
     * Perform actual Stripe sync for a transaction
     */
    private function performStripeSync(Transaction $transaction)
    {
        \Stripe\Stripe::setApiKey(config('services.stripe.secret'));
        
        if ($transaction->stripe_session_id) {
            $session = \Stripe\Checkout\Session::retrieve($transaction->stripe_session_id);
            $this->updateTransactionFromStripeSession($transaction, $session);
        } elseif ($transaction->stripe_payment_intent_id) {
            $paymentIntent = \Stripe\PaymentIntent::retrieve($transaction->stripe_payment_intent_id);
            $this->updateTransactionFromPaymentIntent($transaction, $paymentIntent);
        }
    }

    /**
     * Update transaction from Stripe session
     */
    private function updateTransactionFromStripeSession(Transaction $transaction, $session)
    {
        $newStatus = $this->mapStripeSessionStatus($session);
        
        if ($transaction->status !== $newStatus) {
            $transaction->update(['status' => $newStatus]);
            
            // Update invoice status accordingly
            if ($transaction->invoice) {
                $invoiceStatus = $this->getInvoiceStatusFromTransactionStatus($newStatus);
                $updateData = ['status' => $invoiceStatus];
                
                if ($invoiceStatus === 'paid') {
                    $updateData['paid_at'] = now();
                }
                
                $transaction->invoice->update($updateData);
            }
        }
    }

    /**
     * Update transaction from Stripe payment intent
     */
    private function updateTransactionFromPaymentIntent(Transaction $transaction, $paymentIntent)
    {
        $newStatus = $this->mapStripePaymentIntentStatus($paymentIntent);
        
        if ($transaction->status !== $newStatus) {
            $transaction->update(['status' => $newStatus]);
            
            // Update invoice status accordingly
            if ($transaction->invoice) {
                $invoiceStatus = $this->getInvoiceStatusFromTransactionStatus($newStatus);
                $updateData = ['status' => $invoiceStatus];
                
                if ($invoiceStatus === 'paid') {
                    $updateData['paid_at'] = now();
                }
                
                $transaction->invoice->update($updateData);
            }
        }
    }

    /**
     * Map Stripe session status to transaction status
     */
    private function mapStripeSessionStatus($session)
    {
        switch ($session->status) {
            case 'complete':
                return $session->payment_status === 'paid' ? 'completed' : 'failed';
            case 'expired':
                return 'expired';
            case 'open':
                return 'pending';
            default:
                return 'pending';
        }
    }

    /**
     * Map Stripe payment intent status to transaction status
     */
    private function mapStripePaymentIntentStatus($paymentIntent)
    {
        switch ($paymentIntent->status) {
            case 'succeeded':
                return 'completed';
            case 'payment_failed':
            case 'canceled':
                return 'failed';
            case 'processing':
            case 'requires_payment_method':
            case 'requires_confirmation':
            case 'requires_action':
                return 'pending';
            default:
                return 'pending';
        }
    }

    /**
     * Get human-readable status message from Stripe session
     */
    private function getStripeStatusMessage($session)
    {
        if ($session->status === 'complete') {
            return $session->payment_status === 'paid' ? 
                'Payment completed successfully' : 
                'Payment incomplete - ' . ucfirst($session->payment_status);
        }
        
        return 'Session status: ' . ucfirst($session->status);
    }

    /**
     * Get human-readable status message from Stripe payment intent
     */
    private function getStripePaymentIntentMessage($paymentIntent)
    {
        switch ($paymentIntent->status) {
            case 'succeeded':
                return 'Payment succeeded';
            case 'payment_failed':
                return 'Payment failed';
            case 'canceled':
                return 'Payment was canceled';
            case 'processing':
                return 'Payment is being processed';
            case 'requires_payment_method':
                return 'Requires payment method';
            case 'requires_confirmation':
                return 'Requires confirmation';
            case 'requires_action':
                return 'Requires additional action';
            default:
                return 'Status: ' . ucfirst($paymentIntent->status);
        }
    }

    /**
     * Get invoice status based on transaction status
     */
    private function getInvoiceStatusFromTransactionStatus($transactionStatus)
    {
        switch ($transactionStatus) {
            case 'completed':
                return 'paid';
            case 'refunded':
                return 'refunded';
            case 'failed':
            case 'expired':
            case 'cancelled':
                return 'sent'; // Keep as sent so it can be retried
            default:
                return 'sent';
        }
    }

    /**
     * Get real-time status update via AJAX
     */
    public function getStatus(Transaction $transaction)
    {
        try {
            // Perform sync with Stripe
            $this->performStripeSync($transaction);
            
            // Reload transaction to get updated status
            $transaction->refresh();
            
            return response()->json([
                'success' => true,
                'status' => $transaction->status,
                'status_display' => $this->getStatusDisplay($transaction->status),
                'updated_at' => $transaction->updated_at->format('M d, Y H:i:s'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get formatted status display
     */
    private function getStatusDisplay($status)
    {
        switch ($status) {
            case 'completed':
                return '✓ Payment Successful';
            case 'failed':
                return '✗ Payment Failed';
            case 'expired':
                return '⏰ Payment Expired';
            case 'refunded':
                return '↩ Refunded';
            case 'pending':
                return '⏳ Pending';
            case 'cancelled':
                return '❌ Payment Cancelled';
            default:
                return ucfirst($status);
        }
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Invoice;
use App\Models\Transaction;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;
use Illuminate\Support\Facades\Log;

class StripeWebhookController extends Controller
{
    /**
     * Handle Stripe webhook events
     */
    public function handleWebhook(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $endpointSecret = config('services.stripe.webhook_secret');

        Log::info('Stripe webhook received', ['event_type' => json_decode($payload)->type ?? 'unknown']);

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $endpointSecret);
        } catch (SignatureVerificationException $e) {
            Log::error('Stripe webhook signature verification failed: ' . $e->getMessage());
            return response('Invalid signature', 400);
        }

        Log::info('Stripe webhook event processed', ['event_type' => $event->type, 'event_id' => $event->id]);

        // Handle the event
        switch ($event->type) {
            case 'checkout.session.completed':
                $this->handleCheckoutSessionCompleted($event->data->object);
                break;
            case 'checkout.session.expired':
                $this->handleCheckoutSessionExpired($event->data->object);
                break;
            case 'payment_intent.succeeded':
                $this->handlePaymentIntentSucceeded($event->data->object);
                break;
            case 'payment_intent.payment_failed':
                $this->handlePaymentIntentFailed($event->data->object);
                break;
            default:
                Log::info('Unhandled Stripe event: ' . $event->type);
        }

        return response('Webhook handled', 200);
    }

    /**
     * Handle checkout session completed
     */
    private function handleCheckoutSessionCompleted($session)
    {
        Log::info('Processing checkout session completed', ['session_id' => $session->id]);
        
        $invoiceId = $session->metadata->invoice_id ?? null;
        
        if (!$invoiceId) {
            Log::error('No invoice ID found in Stripe session metadata', ['session_id' => $session->id]);
            return;
        }

        Log::info('Found invoice ID in metadata', ['invoice_id' => $invoiceId, 'session_id' => $session->id]);

        $invoice = Invoice::find($invoiceId);
        
        if (!$invoice) {
            Log::error('Invoice not found for ID: ' . $invoiceId, ['session_id' => $session->id]);
            return;
        }

        Log::info('Invoice found', ['invoice_id' => $invoice->id, 'invoice_number' => $invoice->invoice_number]);

        // Check if transaction already exists for this session
        $existingTransaction = Transaction::where('stripe_session_id', $session->id)->first();
        
        if ($existingTransaction) {
            Log::info('Updating existing transaction for session: ' . $session->id, [
                'existing_transaction_id' => $existingTransaction->id,
                'existing_status' => $existingTransaction->status
            ]);
            
            // Determine new status based on payment status
            $newStatus = $this->mapStripeSessionStatusForWebhook($session);
            
            // Update existing transaction
            $existingTransaction->update(['status' => $newStatus]);
            
            // Update invoice status
            $invoiceStatus = $this->getInvoiceStatusFromTransactionStatus($newStatus);
            $updateData = ['status' => $invoiceStatus];
            if ($invoiceStatus === 'paid') {
                $updateData['paid_at'] = now();
            }
            $invoice->update($updateData);
            
            Log::info('Updated existing transaction', [
                'transaction_id' => $existingTransaction->id,
                'old_status' => $existingTransaction->status,
                'new_status' => $newStatus,
                'invoice_status' => $invoiceStatus,
                'stripe_status' => $session->status,
                'payment_status' => $session->payment_status ?? 'unknown'
            ]);
            
            return;
        }

        // Also check for pending transactions for this invoice (in case of multiple payment attempts)
        $pendingTransactions = Transaction::where('invoice_id', $invoice->id)
            ->where('status', 'pending')
            ->where('stripe_session_id', '!=', $session->id)
            ->get();
            
        if ($pendingTransactions->count() > 0) {
            Log::info('Found pending transactions for same invoice, updating them', [
                'invoice_id' => $invoice->id,
                'pending_count' => $pendingTransactions->count()
            ]);
            
            foreach ($pendingTransactions as $pendingTransaction) {
                $pendingTransaction->update(['status' => 'expired']);
                Log::info('Updated pending transaction to expired', [
                    'transaction_id' => $pendingTransaction->id,
                    'transaction_number' => $pendingTransaction->transaction_number
                ]);
            }
        }

        // Determine transaction status based on payment status
        $transactionStatus = $this->mapStripeSessionStatusForWebhook($session);
        $invoiceStatus = $this->getInvoiceStatusFromTransactionStatus($transactionStatus);

        // Create transaction record
        $transaction = new Transaction();
        $transaction->customer_id = $invoice->customer_id;
        $transaction->invoice_id = $invoice->id;
        $transaction->transaction_number = 'TXN-' . time();
        $transaction->amount = $invoice->total_amount;
        $transaction->payment_method = 'stripe';
        $transaction->status = $transactionStatus;
        $transaction->stripe_session_id = $session->id;
        $transaction->save();

        Log::info('Transaction created', [
            'transaction_id' => $transaction->id, 
            'transaction_number' => $transaction->transaction_number,
            'status' => $transactionStatus,
            'payment_status' => $session->payment_status ?? 'unknown'
        ]);

        // Update invoice status
        $updateData = ['status' => $invoiceStatus];
        if ($invoiceStatus === 'paid') {
            $updateData['paid_at'] = now();
        }
        $invoice->update($updateData);

        Log::info('Payment processed for invoice #' . $invoice->invoice_number, [
            'invoice_id' => $invoice->id,
            'transaction_id' => $transaction->id,
            'session_id' => $session->id,
            'final_status' => $transactionStatus
        ]);
    }

    /**
     * Handle payment intent succeeded
     */
    private function handlePaymentIntentSucceeded($paymentIntent)
    {
        Log::info('Payment intent succeeded: ' . $paymentIntent->id);
        
        // You can add additional logic here if needed
        // For example, if you're using payment intents directly instead of checkout sessions
    }

    /**
     * Handle checkout session expired
     */
    private function handleCheckoutSessionExpired($session)
    {
        Log::info('Processing checkout session expired', ['session_id' => $session->id]);
        
        $invoiceId = $session->metadata->invoice_id ?? null;
        
        if (!$invoiceId) {
            Log::error('No invoice ID found in expired session metadata', ['session_id' => $session->id]);
            return;
        }

        $invoice = Invoice::find($invoiceId);
        
        if (!$invoice) {
            Log::error('Invoice not found for expired session', ['invoice_id' => $invoiceId, 'session_id' => $session->id]);
            return;
        }

        // Check if transaction already exists
        $existingTransaction = Transaction::where('stripe_session_id', $session->id)->first();
        
        if ($existingTransaction) {
            Log::info('Transaction already exists for expired session: ' . $session->id);
            return;
        }

        // Create failed transaction record for expired session
        $transaction = new Transaction();
        $transaction->customer_id = $invoice->customer_id;
        $transaction->invoice_id = $invoice->id;
        $transaction->transaction_number = 'TXN-' . time();
        $transaction->amount = $invoice->total_amount;
        $transaction->payment_method = 'stripe';
        $transaction->status = 'expired';
        $transaction->stripe_session_id = $session->id;
        $transaction->save();

        Log::info('Expired transaction created', [
            'transaction_id' => $transaction->id,
            'transaction_number' => $transaction->transaction_number,
            'invoice_number' => $invoice->invoice_number
        ]);
    }

    /**
     * Handle payment intent failed
     */
    private function handlePaymentIntentFailed($paymentIntent)
    {
        Log::error('Payment intent failed: ' . $paymentIntent->id);
        
        // Find the invoice associated with this payment intent
        $invoice = Invoice::where('stripe_payment_intent_id', $paymentIntent->id)->first();
        
        if ($invoice) {
            // Check if transaction already exists
            $existingTransaction = Transaction::where('stripe_payment_intent_id', $paymentIntent->id)->first();
            
            if (!$existingTransaction) {
                // Create failed transaction record
                $transaction = new Transaction();
                $transaction->customer_id = $invoice->customer_id;
                $transaction->invoice_id = $invoice->id;
                $transaction->transaction_number = 'TXN-' . time();
                $transaction->amount = $invoice->total_amount;
                $transaction->payment_method = 'stripe';
                $transaction->status = 'failed';
                $transaction->stripe_payment_intent_id = $paymentIntent->id;
                $transaction->save();

                Log::info('Failed transaction created for payment intent', [
                    'transaction_id' => $transaction->id,
                    'transaction_number' => $transaction->transaction_number,
                    'invoice_number' => $invoice->invoice_number,
                    'payment_intent_id' => $paymentIntent->id
                ]);
            }
            
            Log::info('Payment failed for invoice #' . $invoice->invoice_number);
        }
    }

    /**
     * Map Stripe session status to transaction status for webhooks
     */
    private function mapStripeSessionStatusForWebhook($session)
    {
        switch ($session->status) {
            case 'complete':
                return isset($session->payment_status) && $session->payment_status === 'paid' ? 'completed' : 'failed';
            case 'expired':
                return 'expired';
            case 'open':
                return 'pending';
            default:
                return 'pending';
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
     * Simple test endpoint to verify webhook is reachable
     */
    public function test()
    {
        Log::info('Webhook test endpoint accessed');
        return response()->json([
            'status' => 'success',
            'message' => 'Webhook endpoint is working',
            'timestamp' => now()->toISOString()
        ]);
    }
} 
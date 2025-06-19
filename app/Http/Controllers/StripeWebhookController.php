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

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $endpointSecret);
        } catch (SignatureVerificationException $e) {
            Log::error('Stripe webhook signature verification failed: ' . $e->getMessage());
            return response('Invalid signature', 400);
        }

        // Handle the event
        switch ($event->type) {
            case 'checkout.session.completed':
                $this->handleCheckoutSessionCompleted($event->data->object);
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
        $invoiceId = $session->metadata->invoice_id ?? null;
        
        if (!$invoiceId) {
            Log::error('No invoice ID found in Stripe session metadata');
            return;
        }

        $invoice = Invoice::find($invoiceId);
        
        if (!$invoice) {
            Log::error('Invoice not found for ID: ' . $invoiceId);
            return;
        }

        // Check if transaction already exists
        $existingTransaction = Transaction::where('stripe_session_id', $session->id)->first();
        
        if ($existingTransaction) {
            Log::info('Transaction already exists for session: ' . $session->id);
            return;
        }

        // Create transaction record
        $transaction = new Transaction();
        $transaction->invoice_id = $invoice->id;
        $transaction->transaction_number = 'TXN-' . time();
        $transaction->amount = $invoice->total_amount;
        $transaction->payment_method = 'stripe';
        $transaction->status = 'completed';
        $transaction->stripe_session_id = $session->id;
        $transaction->save();

        // Update invoice status
        $invoice->update(['status' => 'success']);

        Log::info('Payment completed for invoice #' . $invoice->invoice_number);
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
     * Handle payment intent failed
     */
    private function handlePaymentIntentFailed($paymentIntent)
    {
        Log::error('Payment intent failed: ' . $paymentIntent->id);
        
        // You can add logic to handle failed payments
        // For example, updating invoice status to 'payment_failed'
    }
} 
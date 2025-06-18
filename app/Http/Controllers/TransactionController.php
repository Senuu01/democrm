<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Invoice;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $transactions = Transaction::with(['customer', 'invoice'])
            ->latest()
            ->paginate(10);
        return view('transactions.index', compact('transactions'));
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
    public function show(Transaction $transaction)
    {
        return view('transactions.show', compact('transaction'));
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
            'status' => 'required|in:pending,completed,failed,refunded',
        ]);

        $transaction->update($validated);

        // If transaction is completed, update invoice status
        if ($validated['status'] === 'completed' && $transaction->invoice) {
            $transaction->invoice->update(['status' => 'paid']);
        }

        // If transaction is refunded, update invoice status
        if ($validated['status'] === 'refunded' && $transaction->invoice) {
            $transaction->invoice->update(['status' => 'refunded']);
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
}

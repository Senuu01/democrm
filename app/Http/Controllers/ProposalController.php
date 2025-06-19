<?php

namespace App\Http\Controllers;

use App\Models\Proposal;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\ProposalCreated;

class ProposalController extends Controller
{
    /**
     * Display a listing of the proposals.
     */
    public function index(Request $request)
    {
        $query = Proposal::query();

        // Apply search filter
        if ($request->has('search')) {
            $query->search($request->search);
        }

        // Apply status filter
        if ($request->has('status') && $request->status !== '') {
            $query->status($request->status);
        }

        $proposals = $query->latest()->paginate(10);

        return view('proposals.index', compact('proposals'));
    }

    /**
     * Show the form for creating a new proposal.
     */
    public function create()
    {
        $customers = Customer::where('status', 'active')->get();
        return view('proposals.create', compact('customers'));
    }

    /**
     * Store a newly created proposal in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'valid_until' => 'required|date|after:today',
            'terms_conditions' => 'nullable|string',
        ]);

        $proposal = Proposal::create($validated);

        // Send email notification
        Mail::to($proposal->customer->email)->send(new ProposalCreated($proposal));

        return redirect()->route('proposals.index')
            ->with('success', 'Proposal created successfully.');
    }

    /**
     * Display the specified proposal.
     */
    public function show(Proposal $proposal)
    {
        return view('proposals.show', compact('proposal'));
    }

    /**
     * Show the form for editing the specified proposal.
     */
    public function edit(Proposal $proposal)
    {
        $customers = Customer::where('status', 'active')->get();
        return view('proposals.edit', compact('proposal', 'customers'));
    }

    /**
     * Update the specified proposal in storage.
     */
    public function update(Request $request, Proposal $proposal)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'valid_until' => 'required|date|after:today',
            'terms_conditions' => 'nullable|string',
        ]);

        $proposal->update($validated);

        return redirect()->route('proposals.index')
            ->with('success', 'Proposal updated successfully.');
    }

    /**
     * Remove the specified proposal from storage.
     */
    public function destroy(Proposal $proposal)
    {
        $proposal->delete();

        return redirect()->route('proposals.index')
            ->with('success', 'Proposal deleted successfully.');
    }

    public function updateStatus(Request $request, Proposal $proposal)
    {
        $validated = $request->validate([
            'status' => 'required|in:draft,sent,accepted,declined',
        ]);

        $proposal->update($validated);

        return redirect()->route('proposals.index')
            ->with('success', 'Proposal status updated successfully.');
    }

    /**
     * Send proposal email to customer
     */
    public function sendEmail(Proposal $proposal)
    {
        try {
            Mail::to($proposal->customer->email)->send(new ProposalCreated($proposal));
            
            // Update status to 'sent' if it was 'draft'
            if ($proposal->status === 'draft') {
                $proposal->update(['status' => 'sent']);
            }

            return redirect()->back()
                ->with('success', 'Proposal email sent successfully to ' . $proposal->customer->email);
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to send proposal email: ' . $e->getMessage());
        }
    }
}

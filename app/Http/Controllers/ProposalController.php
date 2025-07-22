<?php

namespace App\Http\Controllers;

use App\Models\Proposal;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\ProposalCreated;
use Barryvdh\DomPDF\Facade\Pdf;

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
     * Generate PDF for proposal
     */
    public function generatePdf(Proposal $proposal)
    {
        $pdf = Pdf::loadView('proposals.pdf', compact('proposal'));
        
        $filename = 'proposal_' . $proposal->id . '_' . date('Y-m-d') . '.pdf';
        
        return $pdf->download($filename);
    }

    /**
     * Send proposal email to customer with PDF attachment
     */
    public function sendEmail(Proposal $proposal)
    {
        try {
            // Generate PDF
            $pdf = Pdf::loadView('proposals.pdf', compact('proposal'));
            $filename = 'proposal_' . $proposal->id . '_' . date('Y-m-d') . '.pdf';
            
            // Send email with PDF attachment
            Mail::send([], [], function ($message) use ($proposal, $pdf, $filename) {
                $message->to($proposal->customer->email)
                        ->subject("Proposal #{$proposal->id} - {$proposal->title}")
                        ->html("
                            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;'>
                                <h2 style='color: #4f46e5; text-align: center;'>New Proposal from Connectly</h2>
                                <div style='background: #f8fafc; padding: 20px; border-radius: 10px; margin: 20px 0;'>
                                    <p><strong>Proposal:</strong> {$proposal->title}</p>
                                    <p><strong>Amount:</strong> $" . number_format($proposal->amount, 2) . "</p>
                                    <p><strong>Valid Until:</strong> " . $proposal->valid_until->format('M d, Y') . "</p>
                                </div>
                                <p>Hi {$proposal->customer->name},</p>
                                <p>Please find your proposal attached as a PDF. We look forward to working with you!</p>
                                <p>Best regards,<br>The Connectly Team</p>
                            </div>
                        ")
                        ->attachData($pdf->output(), $filename, [
                            'mime' => 'application/pdf',
                        ]);
            });
            
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

    /**
     * Duplicate a proposal
     */
    public function duplicate(Proposal $proposal)
    {
        $newProposal = $proposal->replicate();
        $newProposal->title = $proposal->title . ' (Copy)';
        $newProposal->status = 'draft';
        $newProposal->valid_until = now()->addDays(30);
        $newProposal->save();

        return redirect()->route('proposals.edit', $newProposal)
            ->with('success', 'Proposal duplicated successfully.');
    }

    /**
     * Convert proposal to invoice
     */
    public function convertToInvoice(Proposal $proposal)
    {
        // Check if proposal is accepted
        if ($proposal->status !== 'accepted') {
            return redirect()->back()
                ->with('error', 'Only accepted proposals can be converted to invoices.');
        }

        // Create invoice from proposal
        $invoice = \App\Models\Invoice::create([
            'customer_id' => $proposal->customer_id,
            'proposal_id' => $proposal->id,
            'amount' => $proposal->amount,
            'description' => $proposal->description,
            'due_date' => now()->addDays(30),
            'status' => 'pending',
        ]);

        return redirect()->route('invoices.show', $invoice)
            ->with('success', 'Invoice created from proposal successfully.');
    }
}

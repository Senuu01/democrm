<?php

namespace App\Http\Controllers;

use App\Services\SupabaseService;
use Illuminate\Http\Request;

class SupabaseController extends Controller
{
    protected $supabase;

    public function __construct(SupabaseService $supabase)
    {
        $this->supabase = $supabase;
    }

    public function customers()
    {
        $customers = $this->supabase->query('customers');
        return view('customers.index', compact('customers'));
    }

    public function storeCustomer(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email',
            'phone' => 'nullable|string',
            'company' => 'nullable|string'
        ]);

        $result = $this->supabase->insert('customers', $data);
        
        return redirect()->route('customers')->with('success', 'Customer created successfully');
    }

    public function proposals()
    {
        $proposals = $this->supabase->query('proposals');
        return view('proposals.index', compact('proposals'));
    }

    public function storeProposal(Request $request)
    {
        $data = $request->validate([
            'customer_id' => 'required|integer',
            'title' => 'required|string',
            'description' => 'required|string',
            'amount' => 'required|numeric',
            'valid_until' => 'required|date'
        ]);

        $result = $this->supabase->insert('proposals', $data);
        
        return redirect()->route('proposals')->with('success', 'Proposal created successfully');
    }

    public function createTables()
    {
        try {
            // Test users table by querying it
            $usersTable = $this->supabase->query('users');
            $usersExist = true;
        } catch (\Exception $e) {
            $usersExist = false;
        }

        try {
            // Test customers table by querying it
            $customersTable = $this->supabase->query('customers');
            $customersExist = true;
        } catch (\Exception $e) {
            $customersExist = false;
        }

        try {
            // Test proposals table by querying it
            $proposalsTable = $this->supabase->query('proposals');
            $proposalsExist = true;
        } catch (\Exception $e) {
            $proposalsExist = false;
        }

        return response()->json([
            'message' => 'Checked Supabase tables',
            'users_exist' => $usersExist,
            'customers_exist' => $customersExist,
            'proposals_exist' => $proposalsExist,
            'note' => 'Tables will be created automatically when first data is inserted, or create them manually in Supabase dashboard'
        ]);
    }
}
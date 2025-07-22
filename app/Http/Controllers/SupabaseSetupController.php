<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class SupabaseSetupController extends Controller
{
    public function setupDatabase()
    {
        try {
            $supabaseUrl = config('services.supabase.url');
            $serviceRoleKey = config('services.supabase.service_role_key');

            // SQL to create all necessary tables
            $sql = "
                -- Auth Users Table
                CREATE TABLE IF NOT EXISTS auth_users (
                    id SERIAL PRIMARY KEY,
                    name VARCHAR(255) NOT NULL,
                    email VARCHAR(255) UNIQUE NOT NULL,
                    password VARCHAR(255) NOT NULL,
                    company VARCHAR(255),
                    email_verified BOOLEAN DEFAULT FALSE,
                    verification_code VARCHAR(6),
                    verification_expires TIMESTAMP WITH TIME ZONE,
                    reset_code VARCHAR(6),
                    reset_code_expires TIMESTAMP WITH TIME ZONE,
                    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
                    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
                );

                -- Create index on email for faster lookups
                CREATE INDEX IF NOT EXISTS idx_auth_users_email ON auth_users(email);

                -- Customers Table
                CREATE TABLE IF NOT EXISTS customers (
                    id SERIAL PRIMARY KEY,
                    user_id INTEGER REFERENCES auth_users(id) ON DELETE CASCADE,
                    name VARCHAR(255) NOT NULL,
                    email VARCHAR(255) NOT NULL,
                    phone VARCHAR(50),
                    company VARCHAR(255),
                    address TEXT,
                    city VARCHAR(100),
                    state VARCHAR(100),
                    postal_code VARCHAR(20),
                    country VARCHAR(100),
                    status VARCHAR(20) DEFAULT 'active' CHECK (status IN ('active', 'inactive')),
                    notes TEXT,
                    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
                    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
                    deleted_at TIMESTAMP WITH TIME ZONE
                );

                CREATE INDEX IF NOT EXISTS idx_customers_user_id ON customers(user_id);
                CREATE INDEX IF NOT EXISTS idx_customers_email ON customers(email);
                CREATE INDEX IF NOT EXISTS idx_customers_status ON customers(status);

                -- Proposals Table
                CREATE TABLE IF NOT EXISTS proposals (
                    id SERIAL PRIMARY KEY,
                    user_id INTEGER REFERENCES auth_users(id) ON DELETE CASCADE,
                    customer_id INTEGER REFERENCES customers(id) ON DELETE CASCADE,
                    proposal_number VARCHAR(50) UNIQUE NOT NULL,
                    title VARCHAR(255) NOT NULL,
                    description TEXT,
                    amount DECIMAL(15,2) NOT NULL DEFAULT 0,
                    status VARCHAR(20) DEFAULT 'draft' CHECK (status IN ('draft', 'sent', 'accepted', 'rejected')),
                    valid_until DATE,
                    terms_conditions TEXT,
                    notes TEXT,
                    sent_at TIMESTAMP WITH TIME ZONE,
                    accepted_at TIMESTAMP WITH TIME ZONE,
                    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
                    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
                );

                CREATE INDEX IF NOT EXISTS idx_proposals_user_id ON proposals(user_id);
                CREATE INDEX IF NOT EXISTS idx_proposals_customer_id ON proposals(customer_id);
                CREATE INDEX IF NOT EXISTS idx_proposals_status ON proposals(status);
                CREATE INDEX IF NOT EXISTS idx_proposals_proposal_number ON proposals(proposal_number);

                -- Invoices Table
                CREATE TABLE IF NOT EXISTS invoices (
                    id SERIAL PRIMARY KEY,
                    user_id INTEGER REFERENCES auth_users(id) ON DELETE CASCADE,
                    customer_id INTEGER REFERENCES customers(id) ON DELETE CASCADE,
                    proposal_id INTEGER REFERENCES proposals(id) ON DELETE SET NULL,
                    invoice_number VARCHAR(50) UNIQUE NOT NULL,
                    title VARCHAR(255) NOT NULL,
                    description TEXT,
                    amount DECIMAL(15,2) NOT NULL DEFAULT 0,
                    tax_rate DECIMAL(5,2) DEFAULT 0,
                    tax_amount DECIMAL(15,2) DEFAULT 0,
                    total_amount DECIMAL(15,2) NOT NULL DEFAULT 0,
                    status VARCHAR(20) DEFAULT 'draft' CHECK (status IN ('draft', 'sent', 'paid', 'overdue', 'cancelled', 'refunded')),
                    due_date DATE,
                    sent_at TIMESTAMP WITH TIME ZONE,
                    paid_at TIMESTAMP WITH TIME ZONE,
                    stripe_payment_intent_id VARCHAR(255),
                    stripe_checkout_session_id VARCHAR(255),
                    notes TEXT,
                    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
                    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
                );

                CREATE INDEX IF NOT EXISTS idx_invoices_user_id ON invoices(user_id);
                CREATE INDEX IF NOT EXISTS idx_invoices_customer_id ON invoices(customer_id);
                CREATE INDEX IF NOT EXISTS idx_invoices_status ON invoices(status);
                CREATE INDEX IF NOT EXISTS idx_invoices_invoice_number ON invoices(invoice_number);
                CREATE INDEX IF NOT EXISTS idx_invoices_due_date ON invoices(due_date);

                -- Transactions Table
                CREATE TABLE IF NOT EXISTS transactions (
                    id SERIAL PRIMARY KEY,
                    user_id INTEGER REFERENCES auth_users(id) ON DELETE CASCADE,
                    customer_id INTEGER REFERENCES customers(id) ON DELETE CASCADE,
                    invoice_id INTEGER REFERENCES invoices(id) ON DELETE CASCADE,
                    transaction_number VARCHAR(50) UNIQUE NOT NULL,
                    amount DECIMAL(15,2) NOT NULL,
                    payment_method VARCHAR(50) DEFAULT 'stripe',
                    status VARCHAR(20) DEFAULT 'pending' CHECK (status IN ('pending', 'completed', 'failed', 'cancelled', 'refunded', 'expired')),
                    stripe_payment_intent_id VARCHAR(255),
                    stripe_session_id VARCHAR(255),
                    stripe_charge_id VARCHAR(255),
                    notes TEXT,
                    processed_at TIMESTAMP WITH TIME ZONE,
                    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
                    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
                );

                CREATE INDEX IF NOT EXISTS idx_transactions_user_id ON transactions(user_id);
                CREATE INDEX IF NOT EXISTS idx_transactions_customer_id ON transactions(customer_id);
                CREATE INDEX IF NOT EXISTS idx_transactions_invoice_id ON transactions(invoice_id);
                CREATE INDEX IF NOT EXISTS idx_transactions_status ON transactions(status);
                CREATE INDEX IF NOT EXISTS idx_transactions_transaction_number ON transactions(transaction_number);

                -- Activities Table (for activity logging)
                CREATE TABLE IF NOT EXISTS activities (
                    id SERIAL PRIMARY KEY,
                    user_id INTEGER REFERENCES auth_users(id) ON DELETE CASCADE,
                    subject_type VARCHAR(100),
                    subject_id INTEGER,
                    description TEXT NOT NULL,
                    properties JSONB,
                    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
                    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
                );

                CREATE INDEX IF NOT EXISTS idx_activities_user_id ON activities(user_id);
                CREATE INDEX IF NOT EXISTS idx_activities_subject ON activities(subject_type, subject_id);
                CREATE INDEX IF NOT EXISTS idx_activities_created_at ON activities(created_at);
            ";

            // Execute the SQL using Supabase REST API
            $response = Http::withHeaders([
                'apikey' => $serviceRoleKey,
                'Authorization' => 'Bearer ' . $serviceRoleKey,
                'Content-Type' => 'application/json',
                'Prefer' => 'return=minimal'
            ])->post($supabaseUrl . '/rest/v1/rpc/exec', [
                'sql' => $sql
            ]);

            if ($response->successful()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'All Supabase tables created successfully!',
                    'tables_created' => [
                        'auth_users',
                        'customers', 
                        'proposals',
                        'invoices',
                        'transactions',
                        'activities'
                    ],
                    'response' => $response->json()
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to create tables',
                    'response_body' => $response->body(),
                    'status_code' => $response->status()
                ], 500);
            }

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Database setup failed: ' . $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }

    public function checkTables()
    {
        try {
            $supabaseUrl = config('services.supabase.url');
            $serviceRoleKey = config('services.supabase.service_role_key');

            // Check which tables exist
            $sql = "
                SELECT table_name, column_name, data_type, is_nullable
                FROM information_schema.columns 
                WHERE table_schema = 'public' 
                AND table_name IN ('auth_users', 'customers', 'proposals', 'invoices', 'transactions', 'activities')
                ORDER BY table_name, ordinal_position;
            ";

            $response = Http::withHeaders([
                'apikey' => $serviceRoleKey,
                'Authorization' => 'Bearer ' . $serviceRoleKey,
                'Content-Type' => 'application/json'
            ])->post($supabaseUrl . '/rest/v1/rpc/exec', [
                'sql' => $sql
            ]);

            if ($response->successful()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Table check completed',
                    'tables' => $response->json(),
                    'raw_response' => $response->body()
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to check tables',
                    'response' => $response->body(),
                    'status_code' => $response->status()
                ], 500);
            }

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Table check failed: ' . $e->getMessage()
            ], 500);
        }
    }
}
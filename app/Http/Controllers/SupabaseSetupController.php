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

            // Advanced PostgreSQL schema with functions, triggers, and constraints
            $sql = "
                -- Enable necessary extensions
                CREATE EXTENSION IF NOT EXISTS \"uuid-ossp\";
                CREATE EXTENSION IF NOT EXISTS \"pgcrypto\";

                -- Create ENUM types for better data integrity
                DO \$\$ 
                BEGIN
                    IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'user_status') THEN
                        CREATE TYPE user_status AS ENUM ('active', 'inactive', 'suspended');
                    END IF;
                    
                    IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'customer_status') THEN
                        CREATE TYPE customer_status AS ENUM ('active', 'inactive', 'lead', 'prospect');
                    END IF;
                    
                    IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'proposal_status') THEN
                        CREATE TYPE proposal_status AS ENUM ('draft', 'sent', 'viewed', 'accepted', 'rejected', 'expired');
                    END IF;
                    
                    IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'invoice_status') THEN
                        CREATE TYPE invoice_status AS ENUM ('draft', 'sent', 'viewed', 'paid', 'overdue', 'cancelled', 'refunded', 'partially_paid');
                    END IF;
                    
                    IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'transaction_status') THEN
                        CREATE TYPE transaction_status AS ENUM ('pending', 'processing', 'completed', 'failed', 'cancelled', 'refunded', 'expired', 'disputed');
                    END IF;
                    
                    IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'payment_method') THEN
                        CREATE TYPE payment_method AS ENUM ('stripe', 'paypal', 'bank_transfer', 'cash', 'check', 'crypto');
                    END IF;
                END \$\$;

                -- Auth Users Table (Enhanced)
                CREATE TABLE IF NOT EXISTS auth_users (
                    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
                    name VARCHAR(255) NOT NULL CHECK (length(trim(name)) >= 2),
                    email VARCHAR(255) UNIQUE NOT NULL CHECK (email ~* '^[A-Za-z0-9._%-]+@[A-Za-z0-9.-]+\\.[A-Za-z]{2,}$'),
                    email_verified BOOLEAN DEFAULT FALSE,
                    password VARCHAR(255) NOT NULL,
                    company VARCHAR(255),
                    phone VARCHAR(20) CHECK (phone ~ '^[+]?[0-9\\s\\-\\(\\)]+$' OR phone IS NULL),
                    avatar_url TEXT,
                    timezone VARCHAR(50) DEFAULT 'UTC',
                    language VARCHAR(5) DEFAULT 'en',
                    status user_status DEFAULT 'active',
                    last_login_at TIMESTAMP WITH TIME ZONE,
                    last_login_ip INET,
                    
                    -- Email verification
                    verification_code VARCHAR(6) CHECK (verification_code ~ '^[0-9]{6}$' OR verification_code IS NULL),
                    verification_expires TIMESTAMP WITH TIME ZONE,
                    
                    -- Password reset
                    reset_code VARCHAR(6) CHECK (reset_code ~ '^[0-9]{6}$' OR reset_code IS NULL),
                    reset_code_expires TIMESTAMP WITH TIME ZONE,
                    
                    -- Security
                    failed_login_attempts INTEGER DEFAULT 0,
                    locked_until TIMESTAMP WITH TIME ZONE,
                    
                    -- Timestamps
                    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
                    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
                    
                    -- Constraints
                    CONSTRAINT check_verification_code_expiry CHECK (
                        (verification_code IS NULL AND verification_expires IS NULL) OR 
                        (verification_code IS NOT NULL AND verification_expires IS NOT NULL)
                    ),
                    CONSTRAINT check_reset_code_expiry CHECK (
                        (reset_code IS NULL AND reset_code_expires IS NULL) OR 
                        (reset_code IS NOT NULL AND reset_code_expires IS NOT NULL)
                    )
                );

                -- Customers Table (Enhanced)
                CREATE TABLE IF NOT EXISTS customers (
                    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
                    user_id UUID NOT NULL REFERENCES auth_users(id) ON DELETE CASCADE,
                    
                    -- Basic Info
                    name VARCHAR(255) NOT NULL CHECK (length(trim(name)) >= 2),
                    email VARCHAR(255) NOT NULL CHECK (email ~* '^[A-Za-z0-9._%-]+@[A-Za-z0-9.-]+\\.[A-Za-z]{2,}$'),
                    phone VARCHAR(20) CHECK (phone ~ '^[+]?[0-9\\s\\-\\(\\)]+$' OR phone IS NULL),
                    company VARCHAR(255),
                    job_title VARCHAR(255),
                    website VARCHAR(255) CHECK (website ~* '^https?://.*' OR website IS NULL),
                    
                    -- Address
                    address TEXT,
                    city VARCHAR(100),
                    state VARCHAR(100),
                    postal_code VARCHAR(20),
                    country VARCHAR(100),
                    
                    -- Status and Classification
                    status customer_status DEFAULT 'lead',
                    priority INTEGER DEFAULT 1 CHECK (priority BETWEEN 1 AND 5),
                    source VARCHAR(100), -- 'website', 'referral', 'social_media', etc.
                    
                    -- Financial
                    credit_limit DECIMAL(15,2) DEFAULT 0 CHECK (credit_limit >= 0),
                    current_balance DECIMAL(15,2) DEFAULT 0,
                    
                    -- Metadata
                    tags JSONB DEFAULT '[]',
                    custom_fields JSONB DEFAULT '{}',
                    notes TEXT,
                    
                    -- Timestamps
                    last_contacted_at TIMESTAMP WITH TIME ZONE,
                    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
                    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
                    deleted_at TIMESTAMP WITH TIME ZONE,
                    
                    -- Unique constraint per user
                    UNIQUE(user_id, email)
                );

                -- Proposals Table (Enhanced)
                CREATE TABLE IF NOT EXISTS proposals (
                    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
                    user_id UUID NOT NULL REFERENCES auth_users(id) ON DELETE CASCADE,
                    customer_id UUID NOT NULL REFERENCES customers(id) ON DELETE CASCADE,
                    
                    -- Proposal Details
                    proposal_number VARCHAR(50) NOT NULL,
                    title VARCHAR(255) NOT NULL CHECK (length(trim(title)) >= 3),
                    description TEXT,
                    
                    -- Financial
                    subtotal DECIMAL(15,2) NOT NULL DEFAULT 0 CHECK (subtotal >= 0),
                    tax_rate DECIMAL(5,2) DEFAULT 0 CHECK (tax_rate >= 0 AND tax_rate <= 100),
                    tax_amount DECIMAL(15,2) GENERATED ALWAYS AS (subtotal * tax_rate / 100) STORED,
                    discount_amount DECIMAL(15,2) DEFAULT 0 CHECK (discount_amount >= 0),
                    total_amount DECIMAL(15,2) GENERATED ALWAYS AS (subtotal + (subtotal * tax_rate / 100) - discount_amount) STORED,
                    
                    -- Status and Tracking
                    status proposal_status DEFAULT 'draft',
                    priority INTEGER DEFAULT 1 CHECK (priority BETWEEN 1 AND 5),
                    
                    -- Dates
                    valid_until DATE,
                    sent_at TIMESTAMP WITH TIME ZONE,
                    viewed_at TIMESTAMP WITH TIME ZONE,
                    accepted_at TIMESTAMP WITH TIME ZONE,
                    rejected_at TIMESTAMP WITH TIME ZONE,
                    
                    -- Content
                    terms_conditions TEXT,
                    line_items JSONB DEFAULT '[]',
                    attachments JSONB DEFAULT '[]',
                    
                    -- Metadata
                    notes TEXT,
                    internal_notes TEXT,
                    
                    -- Timestamps
                    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
                    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
                    
                    -- Constraints
                    UNIQUE(user_id, proposal_number),
                    CONSTRAINT check_valid_until CHECK (valid_until >= CURRENT_DATE OR valid_until IS NULL),
                    CONSTRAINT check_status_dates CHECK (
                        (status = 'sent' AND sent_at IS NOT NULL) OR status != 'sent'
                    )
                );

                -- Invoices Table (Enhanced)
                CREATE TABLE IF NOT EXISTS invoices (
                    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
                    user_id UUID NOT NULL REFERENCES auth_users(id) ON DELETE CASCADE,
                    customer_id UUID NOT NULL REFERENCES customers(id) ON DELETE CASCADE,
                    proposal_id UUID REFERENCES proposals(id) ON DELETE SET NULL,
                    
                    -- Invoice Details
                    invoice_number VARCHAR(50) NOT NULL,
                    title VARCHAR(255) NOT NULL CHECK (length(trim(title)) >= 3),
                    description TEXT,
                    
                    -- Financial
                    subtotal DECIMAL(15,2) NOT NULL DEFAULT 0 CHECK (subtotal >= 0),
                    tax_rate DECIMAL(5,2) DEFAULT 0 CHECK (tax_rate >= 0 AND tax_rate <= 100),
                    tax_amount DECIMAL(15,2) GENERATED ALWAYS AS (subtotal * tax_rate / 100) STORED,
                    discount_amount DECIMAL(15,2) DEFAULT 0 CHECK (discount_amount >= 0),
                    total_amount DECIMAL(15,2) GENERATED ALWAYS AS (subtotal + (subtotal * tax_rate / 100) - discount_amount) STORED,
                    paid_amount DECIMAL(15,2) DEFAULT 0 CHECK (paid_amount >= 0),
                    balance_due DECIMAL(15,2) GENERATED ALWAYS AS (subtotal + (subtotal * tax_rate / 100) - discount_amount - paid_amount) STORED,
                    
                    -- Status and Tracking
                    status invoice_status DEFAULT 'draft',
                    priority INTEGER DEFAULT 1 CHECK (priority BETWEEN 1 AND 5),
                    
                    -- Dates
                    invoice_date DATE NOT NULL DEFAULT CURRENT_DATE,
                    due_date DATE NOT NULL,
                    sent_at TIMESTAMP WITH TIME ZONE,
                    viewed_at TIMESTAMP WITH TIME ZONE,
                    paid_at TIMESTAMP WITH TIME ZONE,
                    
                    -- Payment Integration
                    stripe_payment_intent_id VARCHAR(255),
                    stripe_checkout_session_id VARCHAR(255),
                    payment_link VARCHAR(500),
                    
                    -- Content
                    line_items JSONB DEFAULT '[]',
                    payment_terms TEXT,
                    attachments JSONB DEFAULT '[]',
                    
                    -- Metadata
                    notes TEXT,
                    internal_notes TEXT,
                    
                    -- Timestamps
                    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
                    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
                    
                    -- Constraints
                    UNIQUE(user_id, invoice_number),
                    CONSTRAINT check_due_date CHECK (due_date >= invoice_date),
                    CONSTRAINT check_paid_amount CHECK (paid_amount <= (subtotal + (subtotal * tax_rate / 100) - discount_amount)),
                    CONSTRAINT check_status_dates CHECK (
                        (status = 'sent' AND sent_at IS NOT NULL) OR status != 'sent'
                    )
                );

                -- Transactions Table (Enhanced)
                CREATE TABLE IF NOT EXISTS transactions (
                    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
                    user_id UUID NOT NULL REFERENCES auth_users(id) ON DELETE CASCADE,
                    customer_id UUID NOT NULL REFERENCES customers(id) ON DELETE CASCADE,
                    invoice_id UUID NOT NULL REFERENCES invoices(id) ON DELETE CASCADE,
                    
                    -- Transaction Details
                    transaction_number VARCHAR(50) NOT NULL,
                    reference_number VARCHAR(255), -- External reference
                    
                    -- Financial
                    amount DECIMAL(15,2) NOT NULL CHECK (amount > 0),
                    fee_amount DECIMAL(15,2) DEFAULT 0 CHECK (fee_amount >= 0),
                    net_amount DECIMAL(15,2) GENERATED ALWAYS AS (amount - fee_amount) STORED,
                    currency VARCHAR(3) DEFAULT 'USD',
                    
                    -- Payment Details
                    payment_method payment_method DEFAULT 'stripe',
                    status transaction_status DEFAULT 'pending',
                    
                    -- Stripe Integration
                    stripe_payment_intent_id VARCHAR(255),
                    stripe_session_id VARCHAR(255),
                    stripe_charge_id VARCHAR(255),
                    stripe_refund_id VARCHAR(255),
                    
                    -- Metadata
                    gateway_response JSONB DEFAULT '{}',
                    failure_reason TEXT,
                    notes TEXT,
                    
                    -- Timestamps
                    processed_at TIMESTAMP WITH TIME ZONE,
                    failed_at TIMESTAMP WITH TIME ZONE,
                    refunded_at TIMESTAMP WITH TIME ZONE,
                    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
                    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
                    
                    -- Constraints
                    UNIQUE(user_id, transaction_number),
                    CONSTRAINT check_status_timestamps CHECK (
                        (status = 'completed' AND processed_at IS NOT NULL) OR status != 'completed'
                    )
                );

                -- Activities Table (Enhanced)
                CREATE TABLE IF NOT EXISTS activities (
                    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
                    user_id UUID NOT NULL REFERENCES auth_users(id) ON DELETE CASCADE,
                    
                    -- Activity Details
                    subject_type VARCHAR(100) NOT NULL, -- 'customer', 'proposal', 'invoice', 'transaction'
                    subject_id UUID NOT NULL,
                    action VARCHAR(100) NOT NULL, -- 'created', 'updated', 'deleted', 'sent', 'viewed'
                    description TEXT NOT NULL,
                    
                    -- Metadata
                    properties JSONB DEFAULT '{}',
                    changes JSONB DEFAULT '{}', -- What changed (old -> new values)
                    ip_address INET,
                    user_agent TEXT,
                    
                    -- Timestamps
                    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
                    
                    -- Index on polymorphic relationship
                    INDEX(subject_type, subject_id)
                );

                -- Create comprehensive indexes for performance
                
                -- Auth Users indexes
                CREATE INDEX IF NOT EXISTS idx_auth_users_email ON auth_users(email);
                CREATE INDEX IF NOT EXISTS idx_auth_users_status ON auth_users(status) WHERE status != 'active';
                CREATE INDEX IF NOT EXISTS idx_auth_users_last_login ON auth_users(last_login_at DESC);
                CREATE INDEX IF NOT EXISTS idx_auth_users_verification ON auth_users(verification_code) WHERE verification_code IS NOT NULL;
                CREATE INDEX IF NOT EXISTS idx_auth_users_reset ON auth_users(reset_code) WHERE reset_code IS NOT NULL;

                -- Customers indexes
                CREATE INDEX IF NOT EXISTS idx_customers_user_id ON customers(user_id);
                CREATE INDEX IF NOT EXISTS idx_customers_email ON customers(email);
                CREATE INDEX IF NOT EXISTS idx_customers_status ON customers(status);
                CREATE INDEX IF NOT EXISTS idx_customers_created_at ON customers(created_at DESC);
                CREATE INDEX IF NOT EXISTS idx_customers_company ON customers(company) WHERE company IS NOT NULL;
                CREATE INDEX IF NOT EXISTS idx_customers_deleted_at ON customers(deleted_at) WHERE deleted_at IS NULL;
                CREATE INDEX IF NOT EXISTS idx_customers_tags ON customers USING GIN(tags);

                -- Proposals indexes
                CREATE INDEX IF NOT EXISTS idx_proposals_user_id ON proposals(user_id);
                CREATE INDEX IF NOT EXISTS idx_proposals_customer_id ON proposals(customer_id);
                CREATE INDEX IF NOT EXISTS idx_proposals_status ON proposals(status);
                CREATE INDEX IF NOT EXISTS idx_proposals_number ON proposals(proposal_number);
                CREATE INDEX IF NOT EXISTS idx_proposals_created_at ON proposals(created_at DESC);
                CREATE INDEX IF NOT EXISTS idx_proposals_total_amount ON proposals(total_amount DESC);
                CREATE INDEX IF NOT EXISTS idx_proposals_valid_until ON proposals(valid_until) WHERE valid_until IS NOT NULL;
                CREATE INDEX IF NOT EXISTS idx_proposals_line_items ON proposals USING GIN(line_items);

                -- Invoices indexes
                CREATE INDEX IF NOT EXISTS idx_invoices_user_id ON invoices(user_id);
                CREATE INDEX IF NOT EXISTS idx_invoices_customer_id ON invoices(customer_id);
                CREATE INDEX IF NOT EXISTS idx_invoices_proposal_id ON invoices(proposal_id) WHERE proposal_id IS NOT NULL;
                CREATE INDEX IF NOT EXISTS idx_invoices_status ON invoices(status);
                CREATE INDEX IF NOT EXISTS idx_invoices_number ON invoices(invoice_number);
                CREATE INDEX IF NOT EXISTS idx_invoices_due_date ON invoices(due_date);
                CREATE INDEX IF NOT EXISTS idx_invoices_total_amount ON invoices(total_amount DESC);
                CREATE INDEX IF NOT EXISTS idx_invoices_balance_due ON invoices(balance_due) WHERE balance_due > 0;
                CREATE INDEX IF NOT EXISTS idx_invoices_stripe_payment_intent ON invoices(stripe_payment_intent_id) WHERE stripe_payment_intent_id IS NOT NULL;
                CREATE INDEX IF NOT EXISTS idx_invoices_line_items ON invoices USING GIN(line_items);

                -- Transactions indexes
                CREATE INDEX IF NOT EXISTS idx_transactions_user_id ON transactions(user_id);
                CREATE INDEX IF NOT EXISTS idx_transactions_customer_id ON transactions(customer_id);
                CREATE INDEX IF NOT EXISTS idx_transactions_invoice_id ON transactions(invoice_id);
                CREATE INDEX IF NOT EXISTS idx_transactions_status ON transactions(status);
                CREATE INDEX IF NOT EXISTS idx_transactions_number ON transactions(transaction_number);
                CREATE INDEX IF NOT EXISTS idx_transactions_amount ON transactions(amount DESC);
                CREATE INDEX IF NOT EXISTS idx_transactions_created_at ON transactions(created_at DESC);
                CREATE INDEX IF NOT EXISTS idx_transactions_processed_at ON transactions(processed_at DESC) WHERE processed_at IS NOT NULL;
                CREATE INDEX IF NOT EXISTS idx_transactions_stripe_payment_intent ON transactions(stripe_payment_intent_id) WHERE stripe_payment_intent_id IS NOT NULL;
                CREATE INDEX IF NOT EXISTS idx_transactions_stripe_session ON transactions(stripe_session_id) WHERE stripe_session_id IS NOT NULL;

                -- Activities indexes
                CREATE INDEX IF NOT EXISTS idx_activities_user_id ON activities(user_id);
                CREATE INDEX IF NOT EXISTS idx_activities_subject ON activities(subject_type, subject_id);
                CREATE INDEX IF NOT EXISTS idx_activities_created_at ON activities(created_at DESC);
                CREATE INDEX IF NOT EXISTS idx_activities_action ON activities(action);
                CREATE INDEX IF NOT EXISTS idx_activities_properties ON activities USING GIN(properties);

                -- Create functions for automatic timestamp updates
                CREATE OR REPLACE FUNCTION update_updated_at_column()
                RETURNS TRIGGER AS \$\$
                BEGIN
                    NEW.updated_at = NOW();
                    RETURN NEW;
                END;
                \$\$ LANGUAGE plpgsql;

                -- Create function to log activities automatically
                CREATE OR REPLACE FUNCTION log_activity()
                RETURNS TRIGGER AS \$\$
                DECLARE
                    action_type TEXT;
                    old_data JSONB;
                    new_data JSONB;
                    changes_data JSONB := '{}'::JSONB;
                BEGIN
                    -- Determine action type
                    IF TG_OP = 'INSERT' THEN
                        action_type := 'created';
                        new_data := row_to_json(NEW)::JSONB;
                    ELSIF TG_OP = 'UPDATE' THEN
                        action_type := 'updated';
                        old_data := row_to_json(OLD)::JSONB;
                        new_data := row_to_json(NEW)::JSONB;
                        -- Calculate changes (simplified)
                        changes_data := jsonb_build_object('old', old_data, 'new', new_data);
                    ELSIF TG_OP = 'DELETE' THEN
                        action_type := 'deleted';
                        old_data := row_to_json(OLD)::JSONB;
                    END IF;

                    -- Insert activity log
                    INSERT INTO activities (
                        user_id,
                        subject_type,
                        subject_id,
                        action,
                        description,
                        changes
                    ) VALUES (
                        COALESCE(NEW.user_id, OLD.user_id),
                        TG_TABLE_NAME,
                        COALESCE(NEW.id, OLD.id),
                        action_type,
                        action_type || ' ' || TG_TABLE_NAME,
                        changes_data
                    );

                    RETURN COALESCE(NEW, OLD);
                END;
                \$\$ LANGUAGE plpgsql;

                -- Create triggers for automatic updated_at timestamps
                DROP TRIGGER IF EXISTS update_auth_users_updated_at ON auth_users;
                CREATE TRIGGER update_auth_users_updated_at
                    BEFORE UPDATE ON auth_users
                    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

                DROP TRIGGER IF EXISTS update_customers_updated_at ON customers;
                CREATE TRIGGER update_customers_updated_at
                    BEFORE UPDATE ON customers
                    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

                DROP TRIGGER IF EXISTS update_proposals_updated_at ON proposals;
                CREATE TRIGGER update_proposals_updated_at
                    BEFORE UPDATE ON proposals
                    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

                DROP TRIGGER IF EXISTS update_invoices_updated_at ON invoices;
                CREATE TRIGGER update_invoices_updated_at
                    BEFORE UPDATE ON invoices
                    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

                DROP TRIGGER IF EXISTS update_transactions_updated_at ON transactions;
                CREATE TRIGGER update_transactions_updated_at
                    BEFORE UPDATE ON transactions
                    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

                -- Create triggers for automatic activity logging
                DROP TRIGGER IF EXISTS log_customer_activity ON customers;
                CREATE TRIGGER log_customer_activity
                    AFTER INSERT OR UPDATE OR DELETE ON customers
                    FOR EACH ROW EXECUTE FUNCTION log_activity();

                DROP TRIGGER IF EXISTS log_proposal_activity ON proposals;
                CREATE TRIGGER log_proposal_activity
                    AFTER INSERT OR UPDATE OR DELETE ON proposals
                    FOR EACH ROW EXECUTE FUNCTION log_activity();

                DROP TRIGGER IF EXISTS log_invoice_activity ON invoices;
                CREATE TRIGGER log_invoice_activity
                    AFTER INSERT OR UPDATE OR DELETE ON invoices
                    FOR EACH ROW EXECUTE FUNCTION log_activity();

                DROP TRIGGER IF EXISTS log_transaction_activity ON transactions;
                CREATE TRIGGER log_transaction_activity
                    AFTER INSERT OR UPDATE OR DELETE ON transactions
                    FOR EACH ROW EXECUTE FUNCTION log_activity();

                -- Create views for commonly used queries
                CREATE OR REPLACE VIEW customer_summary AS
                SELECT 
                    c.*,
                    COUNT(DISTINCT p.id) as total_proposals,
                    COUNT(DISTINCT i.id) as total_invoices,
                    COALESCE(SUM(i.total_amount), 0) as total_invoiced,
                    COALESCE(SUM(i.paid_amount), 0) as total_paid,
                    COALESCE(SUM(i.balance_due), 0) as total_outstanding
                FROM customers c
                LEFT JOIN proposals p ON c.id = p.customer_id
                LEFT JOIN invoices i ON c.id = i.customer_id
                WHERE c.deleted_at IS NULL
                GROUP BY c.id;

                CREATE OR REPLACE VIEW invoice_summary AS
                SELECT 
                    i.*,
                    c.name as customer_name,
                    c.email as customer_email,
                    c.company as customer_company,
                    COUNT(t.id) as payment_count,
                    CASE 
                        WHEN i.status = 'paid' THEN 'Paid'
                        WHEN i.due_date < CURRENT_DATE AND i.status = 'sent' THEN 'Overdue'
                        WHEN i.status = 'sent' THEN 'Outstanding'
                        ELSE INITCAP(i.status::TEXT)
                    END as status_display
                FROM invoices i
                JOIN customers c ON i.customer_id = c.id
                LEFT JOIN transactions t ON i.id = t.invoice_id AND t.status = 'completed'
                GROUP BY i.id, c.id;

                -- Create function to generate sequential numbers
                CREATE OR REPLACE FUNCTION next_proposal_number(user_uuid UUID)
                RETURNS VARCHAR(50) AS \$\$
                DECLARE
                    next_num INTEGER;
                    year_str VARCHAR(4);
                BEGIN
                    year_str := EXTRACT(YEAR FROM CURRENT_DATE)::VARCHAR;
                    
                    SELECT COALESCE(MAX(
                        CAST(SUBSTRING(proposal_number FROM '[0-9]+\$') AS INTEGER)
                    ), 0) + 1
                    INTO next_num
                    FROM proposals 
                    WHERE user_id = user_uuid 
                    AND proposal_number LIKE 'PROP-' || year_str || '-%';
                    
                    RETURN 'PROP-' || year_str || '-' || LPAD(next_num::VARCHAR, 4, '0');
                END;
                \$\$ LANGUAGE plpgsql;

                CREATE OR REPLACE FUNCTION next_invoice_number(user_uuid UUID)
                RETURNS VARCHAR(50) AS \$\$
                DECLARE
                    next_num INTEGER;
                    year_str VARCHAR(4);
                BEGIN
                    year_str := EXTRACT(YEAR FROM CURRENT_DATE)::VARCHAR;
                    
                    SELECT COALESCE(MAX(
                        CAST(SUBSTRING(invoice_number FROM '[0-9]+\$') AS INTEGER)
                    ), 0) + 1
                    INTO next_num
                    FROM invoices 
                    WHERE user_id = user_uuid 
                    AND invoice_number LIKE 'INV-' || year_str || '-%';
                    
                    RETURN 'INV-' || year_str || '-' || LPAD(next_num::VARCHAR, 4, '0');
                END;
                \$\$ LANGUAGE plpgsql;

                -- Insert sample data (optional - remove in production)
                /*
                INSERT INTO auth_users (name, email, password, company, email_verified) VALUES
                ('Demo User', 'demo@connectly.com', '\$2y\$12\$example_hash', 'Connectly Demo', true)
                ON CONFLICT (email) DO NOTHING;
                */
            ";

            // Execute the SQL using Supabase REST API
            $response = Http::withHeaders([
                'apikey' => $serviceRoleKey,
                'Authorization' => 'Bearer ' . $serviceRoleKey,
                'Content-Type' => 'application/json',
                'Prefer' => 'return=minimal'
            ])->timeout(120) // Increased timeout for complex operations
            ->post($supabaseUrl . '/rest/v1/rpc/exec', [
                'sql' => $sql
            ]);

            if ($response->successful()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Advanced PostgreSQL CRM database created successfully!',
                    'features' => [
                        'UUID Primary Keys',
                        'ENUM Types for Data Integrity',
                        'Advanced Constraints & Validation',
                        'Computed Columns for Calculations', 
                        'Comprehensive Indexing',
                        'Automatic Timestamp Updates',
                        'Activity Logging Triggers',
                        'Useful Views for Reporting',
                        'Sequential Number Generators',
                        'JSON Support for Flexible Data',
                        'Full-Text Search Ready'
                    ],
                    'tables_created' => [
                        'auth_users (with security features)',
                        'customers (with CRM enhancements)', 
                        'proposals (with business logic)',
                        'invoices (with payment integration)',
                        'transactions (with Stripe support)',
                        'activities (with automatic logging)'
                    ],
                    'response' => $response->json()
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to create advanced database schema',
                    'response_body' => $response->body(),
                    'status_code' => $response->status()
                ], 500);
            }

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Database setup failed: ' . $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null
            ], 500);
        }
    }

    public function checkTables()
    {
        try {
            $supabaseUrl = config('services.supabase.url');
            $serviceRoleKey = config('services.supabase.service_role_key');

            // Comprehensive table and schema analysis
            $sql = "
                -- Table information
                SELECT 
                    t.table_name,
                    COUNT(c.column_name) as column_count,
                    pg_size_pretty(pg_total_relation_size(schemaname||'.'||tablename)) as table_size
                FROM information_schema.tables t
                LEFT JOIN information_schema.columns c ON t.table_name = c.table_name
                LEFT JOIN pg_tables pt ON t.table_name = pt.tablename
                WHERE t.table_schema = 'public' 
                AND t.table_name IN ('auth_users', 'customers', 'proposals', 'invoices', 'transactions', 'activities')
                GROUP BY t.table_name, schemaname, tablename
                ORDER BY t.table_name;

                -- Index information  
                SELECT 
                    schemaname,
                    tablename,
                    indexname,
                    indexdef
                FROM pg_indexes
                WHERE schemaname = 'public'
                AND tablename IN ('auth_users', 'customers', 'proposals', 'invoices', 'transactions', 'activities')
                ORDER BY tablename, indexname;

                -- Function information
                SELECT 
                    routine_name,
                    routine_type,
                    data_type
                FROM information_schema.routines
                WHERE routine_schema = 'public'
                AND routine_name IN ('update_updated_at_column', 'log_activity', 'next_proposal_number', 'next_invoice_number')
                ORDER BY routine_name;

                -- Trigger information
                SELECT 
                    trigger_name,
                    event_manipulation,
                    event_object_table,
                    action_timing
                FROM information_schema.triggers
                WHERE trigger_schema = 'public'
                AND event_object_table IN ('auth_users', 'customers', 'proposals', 'invoices', 'transactions', 'activities')
                ORDER BY event_object_table, trigger_name;

                -- Enum types
                SELECT 
                    t.typname as enum_name,
                    array_agg(e.enumlabel ORDER BY e.enumsortorder) as enum_values
                FROM pg_type t 
                JOIN pg_enum e ON t.oid = e.enumtypid 
                WHERE t.typname IN ('user_status', 'customer_status', 'proposal_status', 'invoice_status', 'transaction_status', 'payment_method')
                GROUP BY t.typname
                ORDER BY t.typname;
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
                    'message' => 'Advanced database schema analysis completed',
                    'analysis' => $response->json(),
                    'raw_response' => $response->body()
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to analyze database schema',
                    'response' => $response->body(),
                    'status_code' => $response->status()
                ], 500);
            }

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Schema analysis failed: ' . $e->getMessage()
            ], 500);
        }
    }
}
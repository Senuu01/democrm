-- CONNECTLY CRM DATABASE SETUP
-- Run this in your Supabase SQL Editor

-- Create auth_users table (if not exists)
CREATE TABLE IF NOT EXISTS auth_users (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    company VARCHAR(255),
    phone VARCHAR(50),
    timezone VARCHAR(50) DEFAULT 'America/New_York',
    profile_image VARCHAR(255),
    email_verified_at TIMESTAMP WITH TIME ZONE,
    verification_code VARCHAR(10),
    verification_expires TIMESTAMP WITH TIME ZONE,
    reset_code VARCHAR(10),
    reset_expires TIMESTAMP WITH TIME ZONE,
    deleted_at TIMESTAMP WITH TIME ZONE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Create user_preferences table (MISSING - NEEDED FOR SETTINGS)
CREATE TABLE IF NOT EXISTS user_preferences (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL,
    
    -- Notification Preferences
    email_notifications BOOLEAN DEFAULT TRUE,
    customer_notifications BOOLEAN DEFAULT TRUE,
    proposal_notifications BOOLEAN DEFAULT TRUE,
    invoice_notifications BOOLEAN DEFAULT TRUE,
    payment_notifications BOOLEAN DEFAULT TRUE,
    marketing_emails BOOLEAN DEFAULT FALSE,
    weekly_reports BOOLEAN DEFAULT TRUE,
    
    -- Localization Preferences
    language VARCHAR(10) DEFAULT 'en',
    currency VARCHAR(10) DEFAULT 'USD',
    date_format VARCHAR(20) DEFAULT 'MM/DD/YYYY',
    timezone VARCHAR(50) DEFAULT 'America/New_York',
    
    -- UI Preferences
    theme VARCHAR(20) DEFAULT 'light',
    
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    
    FOREIGN KEY (user_id) REFERENCES auth_users(id) ON DELETE CASCADE
);

-- Create customers table (if not exists)
CREATE TABLE IF NOT EXISTS customers (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(50),
    company VARCHAR(255),
    address TEXT,
    status VARCHAR(50) DEFAULT 'active',
    notes TEXT,
    deleted_at TIMESTAMP WITH TIME ZONE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    
    FOREIGN KEY (user_id) REFERENCES auth_users(id) ON DELETE CASCADE
);

-- Create proposals table (if not exists)
CREATE TABLE IF NOT EXISTS proposals (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL,
    customer_id BIGINT NOT NULL,
    proposal_number VARCHAR(50) UNIQUE NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    line_items JSONB,
    amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    status VARCHAR(50) DEFAULT 'draft',
    terms_conditions TEXT,
    sent_at TIMESTAMP WITH TIME ZONE,
    accepted_at TIMESTAMP WITH TIME ZONE,
    rejected_at TIMESTAMP WITH TIME ZONE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    
    FOREIGN KEY (user_id) REFERENCES auth_users(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
);

-- Create invoices table (if not exists)
CREATE TABLE IF NOT EXISTS invoices (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL,
    customer_id BIGINT NOT NULL,
    proposal_id BIGINT,
    invoice_number VARCHAR(50) UNIQUE NOT NULL,
    description TEXT,
    line_items JSONB,
    amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    status VARCHAR(50) DEFAULT 'draft',
    due_date DATE,
    sent_at TIMESTAMP WITH TIME ZONE,
    paid_at TIMESTAMP WITH TIME ZONE,
    stripe_payment_intent_id VARCHAR(255),
    stripe_session_id VARCHAR(255),
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    
    FOREIGN KEY (user_id) REFERENCES auth_users(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (proposal_id) REFERENCES proposals(id) ON DELETE SET NULL
);

-- Create transactions table (if not exists)
CREATE TABLE IF NOT EXISTS transactions (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL,
    invoice_id BIGINT,
    customer_id BIGINT,
    stripe_payment_intent_id VARCHAR(255) UNIQUE,
    stripe_session_id VARCHAR(255),
    stripe_charge_id VARCHAR(255),
    amount DECIMAL(12,2) NOT NULL,
    status VARCHAR(50) DEFAULT 'pending',
    payment_method VARCHAR(50),
    currency VARCHAR(3) DEFAULT 'USD',
    metadata JSONB,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    
    FOREIGN KEY (user_id) REFERENCES auth_users(id) ON DELETE CASCADE,
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
);

-- Create indexes for better performance
CREATE INDEX IF NOT EXISTS idx_auth_users_email ON auth_users(email);
CREATE INDEX IF NOT EXISTS idx_auth_users_deleted_at ON auth_users(deleted_at);

CREATE INDEX IF NOT EXISTS idx_user_preferences_user_id ON user_preferences(user_id);

CREATE INDEX IF NOT EXISTS idx_customers_user_id ON customers(user_id);
CREATE INDEX IF NOT EXISTS idx_customers_email ON customers(email);
CREATE INDEX IF NOT EXISTS idx_customers_status ON customers(status);
CREATE INDEX IF NOT EXISTS idx_customers_deleted_at ON customers(deleted_at);

CREATE INDEX IF NOT EXISTS idx_proposals_user_id ON proposals(user_id);
CREATE INDEX IF NOT EXISTS idx_proposals_customer_id ON proposals(customer_id);
CREATE INDEX IF NOT EXISTS idx_proposals_status ON proposals(status);
CREATE INDEX IF NOT EXISTS idx_proposals_proposal_number ON proposals(proposal_number);

CREATE INDEX IF NOT EXISTS idx_invoices_user_id ON invoices(user_id);
CREATE INDEX IF NOT EXISTS idx_invoices_customer_id ON invoices(customer_id);
CREATE INDEX IF NOT EXISTS idx_invoices_status ON invoices(status);
CREATE INDEX IF NOT EXISTS idx_invoices_invoice_number ON invoices(invoice_number);
CREATE INDEX IF NOT EXISTS idx_invoices_due_date ON invoices(due_date);

CREATE INDEX IF NOT EXISTS idx_transactions_user_id ON transactions(user_id);
CREATE INDEX IF NOT EXISTS idx_transactions_invoice_id ON transactions(invoice_id);
CREATE INDEX IF NOT EXISTS idx_transactions_stripe_payment_intent_id ON transactions(stripe_payment_intent_id);
CREATE INDEX IF NOT EXISTS idx_transactions_status ON transactions(status);

-- Enable Row Level Security (RLS) on all tables
ALTER TABLE auth_users ENABLE ROW LEVEL SECURITY;
ALTER TABLE user_preferences ENABLE ROW LEVEL SECURITY;
ALTER TABLE customers ENABLE ROW LEVEL SECURITY;
ALTER TABLE proposals ENABLE ROW LEVEL SECURITY;
ALTER TABLE invoices ENABLE ROW LEVEL SECURITY;
ALTER TABLE transactions ENABLE ROW LEVEL SECURITY;

-- Create RLS policies for auth_users
CREATE POLICY "Users can view own data" ON auth_users
    FOR SELECT USING (id = (current_setting('app.current_user_id'))::bigint);

CREATE POLICY "Users can update own data" ON auth_users
    FOR UPDATE USING (id = (current_setting('app.current_user_id'))::bigint);

-- Create RLS policies for user_preferences
CREATE POLICY "Users can view own preferences" ON user_preferences
    FOR SELECT USING (user_id = (current_setting('app.current_user_id'))::bigint);

CREATE POLICY "Users can insert own preferences" ON user_preferences
    FOR INSERT WITH CHECK (user_id = (current_setting('app.current_user_id'))::bigint);

CREATE POLICY "Users can update own preferences" ON user_preferences
    FOR UPDATE USING (user_id = (current_setting('app.current_user_id'))::bigint);

CREATE POLICY "Users can delete own preferences" ON user_preferences
    FOR DELETE USING (user_id = (current_setting('app.current_user_id'))::bigint);

-- Create RLS policies for customers
CREATE POLICY "Users can view own customers" ON customers
    FOR SELECT USING (user_id = (current_setting('app.current_user_id'))::bigint AND deleted_at IS NULL);

CREATE POLICY "Users can insert own customers" ON customers
    FOR INSERT WITH CHECK (user_id = (current_setting('app.current_user_id'))::bigint);

CREATE POLICY "Users can update own customers" ON customers
    FOR UPDATE USING (user_id = (current_setting('app.current_user_id'))::bigint);

CREATE POLICY "Users can delete own customers" ON customers
    FOR DELETE USING (user_id = (current_setting('app.current_user_id'))::bigint);

-- Create RLS policies for proposals
CREATE POLICY "Users can view own proposals" ON proposals
    FOR SELECT USING (user_id = (current_setting('app.current_user_id'))::bigint);

CREATE POLICY "Users can insert own proposals" ON proposals
    FOR INSERT WITH CHECK (user_id = (current_setting('app.current_user_id'))::bigint);

CREATE POLICY "Users can update own proposals" ON proposals
    FOR UPDATE USING (user_id = (current_setting('app.current_user_id'))::bigint);

CREATE POLICY "Users can delete own proposals" ON proposals
    FOR DELETE USING (user_id = (current_setting('app.current_user_id'))::bigint);

-- Create RLS policies for invoices
CREATE POLICY "Users can view own invoices" ON invoices
    FOR SELECT USING (user_id = (current_setting('app.current_user_id'))::bigint);

CREATE POLICY "Users can insert own invoices" ON invoices
    FOR INSERT WITH CHECK (user_id = (current_setting('app.current_user_id'))::bigint);

CREATE POLICY "Users can update own invoices" ON invoices
    FOR UPDATE USING (user_id = (current_setting('app.current_user_id'))::bigint);

CREATE POLICY "Users can delete own invoices" ON invoices
    FOR DELETE USING (user_id = (current_setting('app.current_user_id'))::bigint);

-- Create RLS policies for transactions
CREATE POLICY "Users can view own transactions" ON transactions
    FOR SELECT USING (user_id = (current_setting('app.current_user_id'))::bigint);

CREATE POLICY "Users can insert own transactions" ON transactions
    FOR INSERT WITH CHECK (user_id = (current_setting('app.current_user_id'))::bigint);

CREATE POLICY "Users can update own transactions" ON transactions
    FOR UPDATE USING (user_id = (current_setting('app.current_user_id'))::bigint);

-- Create helpful views for analytics
CREATE OR REPLACE VIEW customer_stats AS
SELECT 
    user_id,
    COUNT(*) as total_customers,
    COUNT(CASE WHEN status = 'active' THEN 1 END) as active_customers,
    COUNT(CASE WHEN status = 'inactive' THEN 1 END) as inactive_customers,
    COUNT(CASE WHEN status = 'prospect' THEN 1 END) as prospect_customers,
    COUNT(CASE WHEN created_at >= date_trunc('month', CURRENT_DATE) THEN 1 END) as new_this_month
FROM customers 
WHERE deleted_at IS NULL
GROUP BY user_id;

CREATE OR REPLACE VIEW proposal_stats AS
SELECT 
    user_id,
    COUNT(*) as total_proposals,
    COUNT(CASE WHEN status = 'draft' THEN 1 END) as draft_proposals,
    COUNT(CASE WHEN status = 'sent' THEN 1 END) as sent_proposals,
    COUNT(CASE WHEN status = 'accepted' THEN 1 END) as accepted_proposals,
    COUNT(CASE WHEN status = 'rejected' THEN 1 END) as rejected_proposals,
    SUM(amount) as total_value,
    SUM(CASE WHEN status = 'accepted' THEN amount ELSE 0 END) as accepted_value,
    ROUND(
        CASE WHEN COUNT(*) > 0 
        THEN (COUNT(CASE WHEN status = 'accepted' THEN 1 END) * 100.0 / COUNT(*))
        ELSE 0 END, 2
    ) as conversion_rate
FROM proposals 
GROUP BY user_id;

CREATE OR REPLACE VIEW invoice_stats AS
SELECT 
    user_id,
    COUNT(*) as total_invoices,
    COUNT(CASE WHEN status = 'draft' THEN 1 END) as draft_invoices,
    COUNT(CASE WHEN status = 'sent' THEN 1 END) as sent_invoices,
    COUNT(CASE WHEN status = 'paid' THEN 1 END) as paid_invoices,
    COUNT(CASE WHEN status = 'overdue' THEN 1 END) as overdue_invoices,
    COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_invoices,
    SUM(amount) as total_value,
    SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END) as paid_value,
    SUM(CASE WHEN status IN ('sent', 'overdue') THEN amount ELSE 0 END) as outstanding_value,
    ROUND(
        CASE WHEN COUNT(*) > 0 
        THEN (COUNT(CASE WHEN status = 'paid' THEN 1 END) * 100.0 / COUNT(*))
        ELSE 0 END, 2
    ) as payment_rate
FROM invoices 
GROUP BY user_id;

-- Success message
DO $$
BEGIN
    RAISE NOTICE '✅ CONNECTLY CRM DATABASE SETUP COMPLETE!';
    RAISE NOTICE '';
    RAISE NOTICE '📊 Tables Created:';
    RAISE NOTICE '   - auth_users (with profile fields)';
    RAISE NOTICE '   - user_preferences (NEW - for settings)';
    RAISE NOTICE '   - customers (with soft deletes)';
    RAISE NOTICE '   - proposals (with line items JSON)';
    RAISE NOTICE '   - invoices (with Stripe integration)';
    RAISE NOTICE '   - transactions (payment tracking)';
    RAISE NOTICE '';
    RAISE NOTICE '🔒 Security Features:';
    RAISE NOTICE '   - Row Level Security (RLS) enabled';
    RAISE NOTICE '   - User-scoped data access policies';
    RAISE NOTICE '   - Foreign key constraints';
    RAISE NOTICE '';
    RAISE NOTICE '⚡ Performance Features:';
    RAISE NOTICE '   - Optimized indexes on all key fields';
    RAISE NOTICE '   - Analytics views for dashboard';
    RAISE NOTICE '   - JSON support for line items';
    RAISE NOTICE '';
    RAISE NOTICE '🎯 Your CRM is now ready for:';
    RAISE NOTICE '   ✓ User registration & authentication';
    RAISE NOTICE '   ✓ Customer management';
    RAISE NOTICE '   ✓ Proposal creation & tracking';
    RAISE NOTICE '   ✓ Invoice generation & payment';
    RAISE NOTICE '   ✓ User settings & preferences';
    RAISE NOTICE '   ✓ Complete API functionality';
    RAISE NOTICE '';
    RAISE NOTICE '🚀 Next Steps:';
    RAISE NOTICE '   1. Test user registration at /register';
    RAISE NOTICE '   2. Visit settings at /settings';
    RAISE NOTICE '   3. Create customers, proposals, and invoices';
    RAISE NOTICE '   4. Use the REST API endpoints';
    RAISE NOTICE '';
END $$; 
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ProposalController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CustomLoginController;
use App\Http\Controllers\SupabaseController;

Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now(),
        'php_version' => PHP_VERSION,
        'laravel_version' => app()->version()
    ]);
});

Route::get('/clear-cache', function() {
    try {
        \Artisan::call('route:clear');
        \Artisan::call('config:clear');
        \Artisan::call('view:clear');
        return response()->json([
            'status' => 'success',
            'message' => 'Cache cleared successfully'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage()
        ], 500);
    }
});

Route::get('/', function () {
    try {
        return view('welcome');
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ], 500);
    }
})->name('home');

Route::middleware(['simple.auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Profile routes
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Resource routes
    Route::resource('customers', CustomerController::class);
    Route::post('/customers/{customer}/toggle-status', [CustomerController::class, 'toggleStatus'])->name('customers.toggle-status');
    Route::post('/customers/bulk-action', [CustomerController::class, 'bulkAction'])->name('customers.bulk-action');
    Route::get('/customers-export', [CustomerController::class, 'export'])->name('customers.export');
    
    Route::resource('proposals', ProposalController::class);
    Route::get('/proposals/{proposal}/pdf', [ProposalController::class, 'generatePdf'])->name('proposals.pdf');
    Route::post('/proposals/{proposal}/duplicate', [ProposalController::class, 'duplicate'])->name('proposals.duplicate');
    Route::post('/proposals/{proposal}/convert-to-invoice', [ProposalController::class, 'convertToInvoice'])->name('proposals.convert-to-invoice');
    
    Route::resource('invoices', InvoiceController::class);
    Route::get('/invoices/{invoice}/pdf', [InvoiceController::class, 'generatePdf'])->name('invoices.pdf');
    
    Route::resource('transactions', TransactionController::class)->only(['index', 'show']);
    Route::post('/transactions/{transaction}/refund', [TransactionController::class, 'refund'])->name('transactions.refund');
    Route::patch('/transactions/{transaction}/status', [TransactionController::class, 'updateStatus'])->name('transactions.updateStatus');
    Route::post('/transactions/{transaction}/sync', [TransactionController::class, 'syncWithStripe'])->name('transactions.sync');
    Route::post('/transactions/sync-all-pending', [TransactionController::class, 'syncAllPending'])->name('transactions.syncAllPending');
    Route::get('/transactions/{transaction}/status', [TransactionController::class, 'getStatus'])->name('transactions.getStatus');

    // Invoice payment routes
    Route::post('/invoices/{invoice}/send', [InvoiceController::class, 'send'])->name('invoices.send');
    Route::get('/invoices/{invoice}/payment', [InvoiceController::class, 'payment'])->name('invoices.payment');
    Route::get('/pay/{invoice}', [InvoiceController::class, 'redirectToStripe'])->name('invoices.pay');
    Route::get('/invoices/{invoice}/payment/success', [InvoiceController::class, 'paymentSuccess'])->name('invoices.payment.success');
    Route::get('/invoices/{invoice}/check-payment-status', [InvoiceController::class, 'checkPaymentStatus'])->name('invoices.checkPaymentStatus');
    Route::patch('/invoices/{invoice}/status', [InvoiceController::class, 'changeStatus'])->name('invoices.status');

    // Proposal status routes
    Route::post('/proposals/{proposal}/status', [ProposalController::class, 'updateStatus'])->name('proposals.updateStatus');
    Route::post('/proposals/{proposal}/send-email', [ProposalController::class, 'sendEmail'])->name('proposals.sendEmail');
});

// Stripe webhook (no auth required)
Route::post('/webhook/stripe', [App\Http\Controllers\StripeWebhookController::class, 'handleWebhook'])->name('stripe.webhook');
Route::get('/webhook/stripe/test', [App\Http\Controllers\StripeWebhookController::class, 'test'])->name('stripe.webhook.test');

// Email/Password Authentication with Supabase
Route::get('/login', [App\Http\Controllers\EmailPasswordAuthController::class, 'showLogin'])->name('login');
Route::post('/login', [App\Http\Controllers\EmailPasswordAuthController::class, 'login'])->name('auth.email-login.post');

// Register route - using auth.email-register as primary (most used in views)
Route::get('/register', [App\Http\Controllers\EmailPasswordAuthController::class, 'showRegister'])->name('auth.email-register');
Route::post('/register', [App\Http\Controllers\EmailPasswordAuthController::class, 'register'])->name('auth.email-register.post');

Route::get('/verify-email', [App\Http\Controllers\EmailPasswordAuthController::class, 'showEmailVerification'])->name('auth.verify-email-form');
Route::post('/verify-email', [App\Http\Controllers\EmailPasswordAuthController::class, 'verifyEmail'])->name('auth.verify-email.post');

Route::get('/forgot-password', [App\Http\Controllers\EmailPasswordAuthController::class, 'showForgotPassword'])->name('auth.forgot-password');
Route::post('/forgot-password', [App\Http\Controllers\EmailPasswordAuthController::class, 'sendResetCode'])->name('auth.send-reset-code');

Route::get('/reset-password', [App\Http\Controllers\EmailPasswordAuthController::class, 'showResetPassword'])->name('auth.reset-password-form');
Route::post('/reset-password', [App\Http\Controllers\EmailPasswordAuthController::class, 'resetPassword'])->name('auth.reset-password.post');

Route::post('/logout', [App\Http\Controllers\EmailPasswordAuthController::class, 'logout'])->name('auth.logout');

// Test email route (for debugging mail configuration)
Route::get('/test-email', [App\Http\Controllers\EmailPasswordAuthController::class, 'testEmail'])->name('test.email');

// Old routes (commented out to prevent database access)
// Route::get('/login', [CustomLoginController::class, 'index'])->name('login');
// Route::post('/login', [CustomLoginController::class, 'login']);

// Supabase routes (for testing and data operations)
Route::get('/supabase/customers', [SupabaseController::class, 'customers'])->name('supabase.customers');
Route::post('/supabase/customers', [SupabaseController::class, 'storeCustomer'])->name('supabase.customers.store');
Route::get('/supabase/proposals', [SupabaseController::class, 'proposals'])->name('supabase.proposals');
Route::post('/supabase/proposals', [SupabaseController::class, 'storeProposal'])->name('supabase.proposals.store');
Route::get('/supabase/setup', [SupabaseController::class, 'createTables'])->name('supabase.setup');

// Test route for Supabase integration
Route::get('/test-supabase', function() {
    $supabase = app(\App\Services\SupabaseService::class);
    
    try {
        // Test query
        $users = $supabase->query('users');
        return response()->json([
            'status' => 'success',
            'message' => 'Supabase connection working',
            'users' => $users
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage()
        ], 500);
    }
})->name('test.supabase');

// Test route to check if our registration view works
Route::get('/test-register', function() {
    return view('test-signup');
});

// Test route to check session data
Route::get('/test-session', function() {
    return response()->json([
        'authenticated' => Session::get('authenticated'),
        'user_email' => Session::get('user_email'), 
        'user_data' => Session::get('user_data'),
        'all_session' => Session::all()
    ]);
});

// Supabase Setup Routes
Route::get('/setup-supabase', [App\Http\Controllers\SupabaseSetupController::class, 'setupDatabase'])->name('setup.supabase');
Route::get('/check-tables', [App\Http\Controllers\SupabaseSetupController::class, 'checkTables'])->name('check.tables');

// Clear all database tables
Route::get('/clear-database', function() {
    try {
        // Clear all tables via HTTP DELETE
        $tables = ['transactions', 'invoices', 'proposals', 'customers', 'auth_users'];
        $results = [];
        
        foreach ($tables as $table) {
            try {
                $response = \Illuminate\Support\Facades\Http::withHeaders([
                    'apikey' => config('services.supabase.service_role_key'),
                    'Authorization' => 'Bearer ' . config('services.supabase.service_role_key'),
                ])->delete(config('services.supabase.url') . "/rest/v1/{$table}?id=not.is.null");
                
                $results[$table] = $response->successful() ? 'cleared' : 'failed';
            } catch (Exception $e) {
                $results[$table] = 'error: ' . $e->getMessage();
            }
        }
        
        return response()->json([
            'status' => 'success',
            'message' => 'Database clear attempted',
            'results' => $results
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Database clear failed: ' . $e->getMessage()
        ], 500);
    }
});

// Setup database tables
Route::get('/setup-database', function() {
    $sql = "
-- Create auth_users table for custom authentication
CREATE TABLE IF NOT EXISTS auth_users (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    company VARCHAR(255),
    email_verified BOOLEAN DEFAULT FALSE,
    verification_code VARCHAR(10),
    verification_expires TIMESTAMP,
    reset_code VARCHAR(10),
    reset_expires TIMESTAMP,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Create customers table
CREATE TABLE IF NOT EXISTS customers (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    phone VARCHAR(50),
    company VARCHAR(255),
    address TEXT,
    status VARCHAR(50) DEFAULT 'active',
    notes TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Create proposals table
CREATE TABLE IF NOT EXISTS proposals (
    id BIGSERIAL PRIMARY KEY,
    customer_id BIGINT REFERENCES customers(id) ON DELETE CASCADE,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    amount DECIMAL(10,2) NOT NULL,
    status VARCHAR(50) DEFAULT 'draft',
    valid_until DATE,
    terms TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Create invoices table
CREATE TABLE IF NOT EXISTS invoices (
    id BIGSERIAL PRIMARY KEY,
    customer_id BIGINT REFERENCES customers(id) ON DELETE CASCADE,
    proposal_id BIGINT REFERENCES proposals(id) ON DELETE SET NULL,
    invoice_number VARCHAR(50) UNIQUE NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    amount DECIMAL(10,2) NOT NULL,
    status VARCHAR(50) DEFAULT 'draft',
    due_date DATE,
    paid_at TIMESTAMP,
    stripe_payment_intent_id VARCHAR(255),
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Create transactions table
CREATE TABLE IF NOT EXISTS transactions (
    id BIGSERIAL PRIMARY KEY,
    invoice_id BIGINT REFERENCES invoices(id) ON DELETE CASCADE,
    customer_id BIGINT REFERENCES customers(id) ON DELETE CASCADE,
    stripe_payment_intent_id VARCHAR(255) UNIQUE,
    stripe_charge_id VARCHAR(255),
    amount DECIMAL(10,2) NOT NULL,
    status VARCHAR(50) DEFAULT 'pending',
    payment_method VARCHAR(50),
    currency VARCHAR(3) DEFAULT 'USD',
    metadata JSONB,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Create indexes
CREATE INDEX IF NOT EXISTS idx_customers_email ON customers(email);
CREATE INDEX IF NOT EXISTS idx_customers_status ON customers(status);
CREATE INDEX IF NOT EXISTS idx_proposals_customer_id ON proposals(customer_id);
CREATE INDEX IF NOT EXISTS idx_proposals_status ON proposals(status);
CREATE INDEX IF NOT EXISTS idx_invoices_customer_id ON invoices(customer_id);
CREATE INDEX IF NOT EXISTS idx_invoices_status ON invoices(status);
CREATE INDEX IF NOT EXISTS idx_invoices_invoice_number ON invoices(invoice_number);
CREATE INDEX IF NOT EXISTS idx_transactions_invoice_id ON transactions(invoice_id);
CREATE INDEX IF NOT EXISTS idx_transactions_stripe_payment_intent_id ON transactions(stripe_payment_intent_id);
CREATE INDEX IF NOT EXISTS idx_transactions_status ON transactions(status);
CREATE INDEX IF NOT EXISTS idx_auth_users_email ON auth_users(email);
";

    return response()->json([
        'status' => 'ready',
        'message' => 'Copy this SQL to your Supabase SQL Editor and run it:',
        'sql' => $sql,
        'instructions' => [
            '1. Go to your Supabase Dashboard',
            '2. Click on SQL Editor in the left sidebar', 
            '3. Create a new query',
            '4. Copy and paste the SQL above',
            '5. Click Run to execute',
            '6. All tables will be created with proper relationships'
        ]
    ]);
});

// require __DIR__.'/auth.php'; // Commented out to use SimpleAuth instead

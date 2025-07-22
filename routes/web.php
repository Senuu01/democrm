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
        \Artisan::call('cache:clear');
        return response()->json([
            'status' => 'success',
            'message' => 'All caches cleared successfully',
            'cleared' => [
                'config' => 'Configuration cache cleared',
                'routes' => 'Route cache cleared', 
                'views' => 'View cache cleared',
                'cache' => 'Application cache cleared'
            ]
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
    // Professional Dashboard with Supabase Analytics
    Route::get('/dashboard', [App\Http\Controllers\SupabaseDashboardController::class, 'index'])->name('dashboard');


    
    // Route::resource('transactions', TransactionController::class)->only(['index', 'show']);
    // Route::post('/transactions/{transaction}/refund', [TransactionController::class, 'refund'])->name('transactions.refund');
    // Route::patch('/transactions/{transaction}/status', [TransactionController::class, 'updateStatus'])->name('transactions.updateStatus');
    // Route::post('/transactions/{transaction}/sync', [TransactionController::class, 'syncWithStripe'])->name('transactions.sync');
    // Route::post('/transactions/sync-all-pending', [TransactionController::class, 'syncAllPending'])->name('transactions.syncAllPending');
    // Route::get('/transactions/{transaction}/status', [TransactionController::class, 'getStatus'])->name('transactions.getStatus');

    // Invoice payment routes - DISABLED (use database)
    // Route::post('/invoices/{invoice}/send', [InvoiceController::class, 'send'])->name('invoices.send');
    // Route::get('/invoices/{invoice}/payment', [InvoiceController::class, 'payment'])->name('invoices.payment');
    // Route::get('/pay/{invoice}', [InvoiceController::class, 'redirectToStripe'])->name('invoices.pay');
    // Route::get('/invoices/{invoice}/payment/success', [InvoiceController::class, 'paymentSuccess'])->name('invoices.payment.success');
    // Route::get('/invoices/{invoice}/check-payment-status', [InvoiceController::class, 'checkPaymentStatus'])->name('invoices.checkPaymentStatus');
    // Route::patch('/invoices/{invoice}/status', [InvoiceController::class, 'changeStatus'])->name('invoices.status');

    // Proposal status routes - DISABLED (use database)
    // Route::post('/proposals/{proposal}/status', [ProposalController::class, 'updateStatus'])->name('proposals.updateStatus');
    // Route::post('/proposals/{proposal}/send-email', [ProposalController::class, 'sendEmail'])->name('proposals.sendEmail');
    
    // SUPABASE-BASED CUSTOMER MANAGEMENT (Full CRUD)
    Route::get('/customers', [App\Http\Controllers\SupabaseCustomerController::class, 'index'])->name('customers.index');
    Route::get('/customers/create', [App\Http\Controllers\SupabaseCustomerController::class, 'create'])->name('customers.create');
    Route::post('/customers', [App\Http\Controllers\SupabaseCustomerController::class, 'store'])->name('customers.store');
    Route::get('/customers/{id}', [App\Http\Controllers\SupabaseCustomerController::class, 'show'])->name('customers.show');
    Route::get('/customers/{id}/edit', [App\Http\Controllers\SupabaseCustomerController::class, 'edit'])->name('customers.edit');
    Route::put('/customers/{id}', [App\Http\Controllers\SupabaseCustomerController::class, 'update'])->name('customers.update');
    Route::delete('/customers/{id}', [App\Http\Controllers\SupabaseCustomerController::class, 'destroy'])->name('customers.destroy');
    Route::post('/customers/{id}/toggle-status', [App\Http\Controllers\SupabaseCustomerController::class, 'toggleStatus'])->name('customers.toggle-status');
    Route::get('/customers-export', [App\Http\Controllers\SupabaseCustomerController::class, 'export'])->name('customers.export');
    
    // SUPABASE-BASED PROPOSAL MANAGEMENT (Full CRUD)
    Route::get('/proposals', [App\Http\Controllers\SupabaseProposalController::class, 'index'])->name('proposals.index');
    Route::get('/proposals/create', [App\Http\Controllers\SupabaseProposalController::class, 'create'])->name('proposals.create');
    Route::post('/proposals', [App\Http\Controllers\SupabaseProposalController::class, 'store'])->name('proposals.store');
    Route::get('/proposals/{id}', [App\Http\Controllers\SupabaseProposalController::class, 'show'])->name('proposals.show');
    Route::get('/proposals/{id}/edit', [App\Http\Controllers\SupabaseProposalController::class, 'edit'])->name('proposals.edit');
    Route::put('/proposals/{id}', [App\Http\Controllers\SupabaseProposalController::class, 'update'])->name('proposals.update');
    Route::delete('/proposals/{id}', [App\Http\Controllers\SupabaseProposalController::class, 'destroy'])->name('proposals.destroy');
    Route::post('/proposals/{id}/status', [App\Http\Controllers\SupabaseProposalController::class, 'updateStatus'])->name('proposals.updateStatus');
    Route::get('/proposals/{id}/pdf', [App\Http\Controllers\SupabaseProposalController::class, 'generatePdf'])->name('proposals.pdf');
    Route::post('/proposals/{id}/send-email', [App\Http\Controllers\SupabaseProposalController::class, 'sendEmail'])->name('proposals.sendEmail');
    Route::post('/proposals/{id}/convert-to-invoice', [App\Http\Controllers\SupabaseProposalController::class, 'convertToInvoice'])->name('proposals.convertToInvoice');
    
    // SUPABASE-BASED INVOICE MANAGEMENT (Full CRUD + Stripe)
    Route::get('/invoices', [App\Http\Controllers\SupabaseInvoiceController::class, 'index'])->name('invoices.index');
    Route::get('/invoices/create', [App\Http\Controllers\SupabaseInvoiceController::class, 'create'])->name('invoices.create');
    Route::post('/invoices', [App\Http\Controllers\SupabaseInvoiceController::class, 'store'])->name('invoices.store');
    Route::get('/invoices/{id}', [App\Http\Controllers\SupabaseInvoiceController::class, 'show'])->name('invoices.show');
    Route::get('/invoices/{id}/edit', [App\Http\Controllers\SupabaseInvoiceController::class, 'edit'])->name('invoices.edit');
    Route::put('/invoices/{id}', [App\Http\Controllers\SupabaseInvoiceController::class, 'update'])->name('invoices.update');
    Route::post('/invoices/{id}/status', [App\Http\Controllers\SupabaseInvoiceController::class, 'updateStatus'])->name('invoices.updateStatus');
    Route::get('/invoices/{id}/pdf', [App\Http\Controllers\SupabaseInvoiceController::class, 'generatePdf'])->name('invoices.pdf');
    Route::post('/invoices/{id}/send-email', [App\Http\Controllers\SupabaseInvoiceController::class, 'sendEmail'])->name('invoices.sendEmail');
    Route::get('/invoices/{id}/payment', [App\Http\Controllers\SupabaseInvoiceController::class, 'createPaymentSession'])->name('invoices.payment');
    Route::get('/invoices/{id}/payment/success', [App\Http\Controllers\SupabaseInvoiceController::class, 'paymentSuccess'])->name('invoices.payment.success');
    
    // USER SETTINGS & PROFILE MANAGEMENT
    Route::get('/settings', [App\Http\Controllers\UserSettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings/profile', [App\Http\Controllers\UserSettingsController::class, 'updateProfile'])->name('settings.profile');
    Route::post('/settings/password', [App\Http\Controllers\UserSettingsController::class, 'updatePassword'])->name('settings.password');
    Route::post('/settings/preferences', [App\Http\Controllers\UserSettingsController::class, 'updatePreferences'])->name('settings.preferences');
    Route::post('/settings/delete', [App\Http\Controllers\UserSettingsController::class, 'deleteAccount'])->name('settings.delete');
    Route::get('/settings/export', [App\Http\Controllers\UserSettingsController::class, 'exportData'])->name('settings.export');
    
    // ANALYTICS DASHBOARD
    Route::get('/analytics', [App\Http\Controllers\AnalyticsController::class, 'index'])->name('analytics.index');
});

// API DOCUMENTATION (Public)
Route::get('/api/docs', function () {
    return view('api.docs');
})->name('api.docs');

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
Route::post('/resend-verification', [App\Http\Controllers\EmailPasswordAuthController::class, 'resendVerificationCode'])->name('auth.resend-verification');

Route::get('/forgot-password', [App\Http\Controllers\EmailPasswordAuthController::class, 'showForgotPassword'])->name('auth.forgot-password');
Route::post('/forgot-password', [App\Http\Controllers\EmailPasswordAuthController::class, 'sendResetCode'])->name('auth.send-reset-code');

Route::get('/reset-password', [App\Http\Controllers\EmailPasswordAuthController::class, 'showResetPassword'])->name('auth.reset-password-form');
Route::post('/reset-password', [App\Http\Controllers\EmailPasswordAuthController::class, 'resetPassword'])->name('auth.reset-password.post');

Route::post('/logout', [App\Http\Controllers\EmailPasswordAuthController::class, 'logout'])->name('auth.logout');

// Test email route (for debugging mail configuration)
Route::get('/test-email', [App\Http\Controllers\EmailPasswordAuthController::class, 'testEmail'])->name('test.email');

// Database debugging routes
Route::get('/create-test-user', [App\Http\Controllers\EmailPasswordAuthController::class, 'createTestUser'])->name('test.create-user');
Route::get('/clear-all-users', [App\Http\Controllers\EmailPasswordAuthController::class, 'clearAllUsers'])->name('test.clear-users');

// Debug page
Route::get('/debug', function () {
    return view('debug');
})->name('debug');

// Check configuration
Route::get('/check-config', function() {
    return response()->json([
        'database' => [
            'connection' => config('database.default'),
            'status' => config('database.default') === 'null' ? '✅ Disabled (Good!)' : '❌ Enabled (May cause issues)'
        ],
        'session' => [
            'driver' => config('session.driver'),
            'status' => config('session.driver') === 'file' ? '✅ File-based (Good!)' : '❌ Database-based (May cause issues)'
        ],
        'queue' => [
            'connection' => config('queue.default'),
            'status' => config('queue.default') === 'sync' ? '✅ Sync (Good!)' : '❌ Database-based (May cause issues)'
        ],
        'cache' => [
            'store' => config('cache.default'),
            'status' => in_array(config('cache.default'), ['file', 'array']) ? '✅ File/Array (Good!)' : '❌ Database-based (May cause issues)'
        ],
        'supabase' => [
            'url' => config('services.supabase.url') ? '✅ Configured' : '❌ Missing',
            'service_key' => config('services.supabase.service_role_key') ? '✅ Configured' : '❌ Missing',
            'anon_key' => config('services.supabase.anon_key') ? '✅ Configured' : '❌ Missing'
        ],
        'mail' => [
            'mailer' => config('mail.default'),
            'from_address' => config('mail.from.address')
        ]
    ]);
});

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

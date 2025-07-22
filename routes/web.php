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

// Simple Email Authentication (No Database) - Direct routes
Route::get('/login', [App\Http\Controllers\SimpleAuthController::class, 'showLogin'])->name('login');
Route::post('/login', [App\Http\Controllers\SimpleAuthController::class, 'sendLoginCode'])->name('auth.send-code');
Route::get('/verify', [App\Http\Controllers\SimpleAuthController::class, 'showVerifyCode'])->name('auth.verify');
Route::post('/verify', [App\Http\Controllers\SimpleAuthController::class, 'verifyCode'])->name('auth.verify.post');
Route::get('/register', [App\Http\Controllers\SimpleAuthController::class, 'showRegister'])->name('register');
Route::post('/register', [App\Http\Controllers\SimpleAuthController::class, 'register'])->name('register.post');
Route::post('/logout', [App\Http\Controllers\SimpleAuthController::class, 'logout'])->name('auth.logout');

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

// Setup route to create Supabase users table
Route::get('/setup-database', function() {
    try {
        $supabase = app(\App\Services\SupabaseService::class);
        
        // First, try to create the users table directly via SQL
        $response = \Illuminate\Support\Facades\Http::withHeaders([
            'apikey' => config('services.supabase.service_role_key'),
            'Authorization' => 'Bearer ' . config('services.supabase.service_role_key'),
            'Content-Type' => 'application/json',
        ])->post(config('services.supabase.url') . '/rest/v1/rpc/exec', [
            'sql' => "
                CREATE TABLE IF NOT EXISTS users (
                    id SERIAL PRIMARY KEY,
                    name VARCHAR(255) NOT NULL,
                    email VARCHAR(255) UNIQUE NOT NULL,
                    company VARCHAR(255),
                    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
                    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
                );
                
                -- Insert a test user
                INSERT INTO users (name, email, company) 
                VALUES ('Test User', 'test@example.com', 'Test Company')
                ON CONFLICT (email) DO NOTHING;
            "
        ]);
        
        return response()->json([
            'status' => 'success',
            'message' => 'Database setup complete',
            'response' => $response->json()
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Database setup failed: ' . $e->getMessage()
        ], 500);
    }
});

// require __DIR__.'/auth.php'; // Commented out to use SimpleAuth instead

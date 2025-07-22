<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ApiAuthController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\ProposalController;
use App\Http\Controllers\Api\InvoiceController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// API Authentication Routes
Route::prefix('auth')->group(function () {
    Route::post('/login', [ApiAuthController::class, 'login'])->name('api.auth.login');
    Route::post('/refresh', [ApiAuthController::class, 'refresh'])->middleware('api.auth')->name('api.auth.refresh');
    Route::get('/me', [ApiAuthController::class, 'me'])->middleware('api.auth')->name('api.auth.me');
    Route::post('/logout', [ApiAuthController::class, 'logout'])->middleware('api.auth')->name('api.auth.logout');
});

// Protected API Routes
Route::middleware('api.auth')->group(function () {
    
    // Customer Management API
    Route::prefix('customers')->name('api.customers.')->group(function () {
        Route::get('/', [CustomerController::class, 'index'])->name('index');
        Route::post('/', [CustomerController::class, 'store'])->name('store');
        Route::get('/stats', [CustomerController::class, 'stats'])->name('stats');
        Route::post('/bulk', [CustomerController::class, 'bulk'])->name('bulk');
        Route::get('/{id}', [CustomerController::class, 'show'])->name('show');
        Route::put('/{id}', [CustomerController::class, 'update'])->name('update');
        Route::patch('/{id}', [CustomerController::class, 'update'])->name('patch');
        Route::delete('/{id}', [CustomerController::class, 'destroy'])->name('destroy');
    });
    
    // Proposal Management API
    Route::prefix('proposals')->name('api.proposals.')->group(function () {
        Route::get('/', [ProposalController::class, 'index'])->name('index');
        Route::post('/', [ProposalController::class, 'store'])->name('store');
        Route::get('/stats', [ProposalController::class, 'stats'])->name('stats');
        Route::get('/{id}', [ProposalController::class, 'show'])->name('show');
        Route::put('/{id}', [ProposalController::class, 'update'])->name('update');
        Route::patch('/{id}', [ProposalController::class, 'update'])->name('patch');
        Route::delete('/{id}', [ProposalController::class, 'destroy'])->name('destroy');
        Route::patch('/{id}/status', [ProposalController::class, 'updateStatus'])->name('status');
    });
    
    // Invoice Management API
    Route::prefix('invoices')->name('api.invoices.')->group(function () {
        Route::get('/', [InvoiceController::class, 'index'])->name('index');
        Route::post('/', [InvoiceController::class, 'store'])->name('store');
        Route::get('/stats', [InvoiceController::class, 'stats'])->name('stats');
        Route::get('/{id}', [InvoiceController::class, 'show'])->name('show');
        Route::put('/{id}', [InvoiceController::class, 'update'])->name('update');
        Route::patch('/{id}', [InvoiceController::class, 'update'])->name('patch');
        Route::delete('/{id}', [InvoiceController::class, 'destroy'])->name('destroy');
        Route::patch('/{id}/status', [InvoiceController::class, 'updateStatus'])->name('status');
        Route::post('/{id}/payment', [InvoiceController::class, 'createPayment'])->name('payment');
    });
    
    // Health Check
    Route::get('/health', function () {
        return response()->json([
            'success' => true,
            'message' => 'API is healthy',
            'timestamp' => now()->toISOString(),
            'version' => '1.0.0',
            'environment' => config('app.env'),
            'database' => 'Supabase',
            'services' => [
                'supabase' => config('services.supabase.url') ? 'connected' : 'disconnected',
                'mail' => config('mail.mailers.smtp.host') ? 'configured' : 'not configured'
            ]
        ]);
    })->name('api.health');
    
    // User Info
    Route::get('/user', function (Request $request) {
        return response()->json([
            'success' => true,
            'message' => 'User information retrieved successfully',
            'data' => $request->user()
        ]);
    })->name('api.user');
});

// Public API Info
Route::get('/info', function () {
    return response()->json([
        'name' => 'Connectly CRM API',
        'version' => '1.0.0',
        'description' => 'REST API for Connectly CRM system',
        'documentation' => url('/api/docs'),
        'base_url' => url('/api'),
        'endpoints' => [
            'authentication' => [
                'POST /api/auth/login' => 'Login and get access token',
                'POST /api/auth/refresh' => 'Refresh access token',
                'GET /api/auth/me' => 'Get current user info',
                'POST /api/auth/logout' => 'Logout and invalidate token'
            ],
            'customers' => [
                'GET /api/customers' => 'Get customers list with pagination and search',
                'POST /api/customers' => 'Create new customer',
                'GET /api/customers/{id}' => 'Get specific customer with optional includes',
                'PUT /api/customers/{id}' => 'Update customer (full update)',
                'PATCH /api/customers/{id}' => 'Update customer (partial update)',
                'DELETE /api/customers/{id}' => 'Soft delete customer',
                'GET /api/customers/stats' => 'Get customer statistics',
                'POST /api/customers/bulk' => 'Bulk operations (delete/update_status)'
            ],
            'proposals' => [
                'GET /api/proposals' => 'Get proposals list with pagination and search',
                'POST /api/proposals' => 'Create new proposal with line items',
                'GET /api/proposals/{id}' => 'Get specific proposal with optional includes',
                'PUT /api/proposals/{id}' => 'Update proposal (full update)',
                'PATCH /api/proposals/{id}' => 'Update proposal (partial update)',
                'DELETE /api/proposals/{id}' => 'Delete proposal (draft only)',
                'GET /api/proposals/stats' => 'Get proposal statistics and conversion rates',
                'PATCH /api/proposals/{id}/status' => 'Update proposal status'
            ],
            'invoices' => [
                'GET /api/invoices' => 'Get invoices list with pagination and search',
                'POST /api/invoices' => 'Create new invoice with line items',
                'GET /api/invoices/{id}' => 'Get specific invoice with optional includes',
                'PUT /api/invoices/{id}' => 'Update invoice (full update)',
                'PATCH /api/invoices/{id}' => 'Update invoice (partial update)',
                'DELETE /api/invoices/{id}' => 'Delete invoice (draft/cancelled only)',
                'GET /api/invoices/stats' => 'Get invoice statistics and payment rates',
                'PATCH /api/invoices/{id}/status' => 'Update invoice status',
                'POST /api/invoices/{id}/payment' => 'Create Stripe payment session'
            ],
            'system' => [
                'GET /api/health' => 'API health check',
                'GET /api/user' => 'Get current authenticated user',
                'GET /api/info' => 'Get API information (this endpoint)'
            ]
        ],
        'authentication' => [
            'type' => 'Bearer Token',
            'header' => 'Authorization: Bearer {token}',
            'expires' => '30 days',
            'refresh' => 'Use /api/auth/refresh endpoint'
        ],
        'pagination' => [
            'default_limit' => 10,
            'max_limit' => 100,
            'parameters' => ['limit', 'offset']
        ],
        'search' => [
            'customers' => 'Search by name, email, or company',
            'parameter' => 'search'
        ],
        'filtering' => [
            'customers' => 'Filter by status (active/inactive/prospect)',
            'parameter' => 'status'
        ],
        'sorting' => [
            'parameter' => 'sort',
            'order_parameter' => 'order',
            'default' => 'created_at.desc'
        ],
        'rate_limit' => '1000 requests per hour',
        'response_format' => [
            'success' => 'boolean',
            'message' => 'string',
            'data' => 'object|array',
            'meta' => 'object (for paginated responses)',
            'errors' => 'object (for validation errors)'
        ],
        'status_codes' => [
            200 => 'Success',
            201 => 'Created',
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            409 => 'Conflict (duplicate data)',
            422 => 'Validation Error',
            500 => 'Internal Server Error'
        ]
    ]);
})->name('api.info');
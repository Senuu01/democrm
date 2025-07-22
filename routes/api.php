<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\ProposalController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\AuthController;

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

// Authentication routes
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

Route::middleware('auth:sanctum')->group(function () {
    // User info
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    
    Route::post('/logout', [AuthController::class, 'logout']);
    
    // Customer API endpoints
    Route::apiResource('customers', CustomerController::class);
    Route::post('customers/{customer}/toggle-status', [CustomerController::class, 'toggleStatus']);
    
    // Proposal API endpoints
    Route::apiResource('proposals', ProposalController::class);
    Route::post('proposals/{proposal}/send-email', [ProposalController::class, 'sendEmail']);
    Route::patch('proposals/{proposal}/status', [ProposalController::class, 'updateStatus']);
    
    // Invoice API endpoints
    Route::apiResource('invoices', InvoiceController::class);
    Route::post('invoices/{invoice}/send', [InvoiceController::class, 'send']);
    Route::patch('invoices/{invoice}/status', [InvoiceController::class, 'changeStatus']);
    
    // Analytics endpoints (admin only)
    Route::middleware('admin')->group(function () {
        Route::get('/analytics/dashboard', function () {
            $dashboardController = new \App\Http\Controllers\DashboardController();
            $data = $dashboardController->index()->getData();
            return response()->json($data);
        });
        
        Route::get('/analytics/revenue', function () {
            $revenue = \App\Models\Transaction::where('status', 'completed')
                ->selectRaw('DATE(created_at) as date, SUM(amount) as total')
                ->groupBy('date')
                ->orderBy('date', 'desc')
                ->take(30)
                ->get();
            
            return response()->json(['revenue' => $revenue]);
        });
    });
});
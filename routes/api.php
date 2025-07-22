<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
// use App\Http\Controllers\Api\CustomerController;
// use App\Http\Controllers\Api\ProposalController;
// use App\Http\Controllers\Api\InvoiceController;
// use App\Http\Controllers\Api\AuthController;

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

// Authentication routes - API controllers not yet implemented
// Route::post('/login', [AuthController::class, 'login']);
// Route::post('/register', [AuthController::class, 'register']);

// Route::middleware('auth:sanctum')->group(function () {
//     // User info
//     Route::get('/user', function (Request $request) {
//         return $request->user();
//     });
//     
//     Route::post('/logout', [AuthController::class, 'logout']);
//     
//     // Customer API endpoints
//     Route::apiResource('customers', CustomerController::class)->names([
//         'index' => 'api.customers.index',
//         'store' => 'api.customers.store',
//         'show' => 'api.customers.show',
//         'update' => 'api.customers.update',
//         'destroy' => 'api.customers.destroy',
//     ]);
//     Route::post('customers/{customer}/toggle-status', [CustomerController::class, 'toggleStatus'])->name('api.customers.toggle-status');
//     
//     // Proposal API endpoints
//     Route::apiResource('proposals', ProposalController::class)->names([
//         'index' => 'api.proposals.index',
//         'store' => 'api.proposals.store',
//         'show' => 'api.proposals.show',
//         'update' => 'api.proposals.update',
//         'destroy' => 'api.proposals.destroy',
//     ]);
//     Route::post('proposals/{proposal}/send-email', [ProposalController::class, 'sendEmail'])->name('api.proposals.send-email');
//     Route::patch('proposals/{proposal}/status', [ProposalController::class, 'updateStatus'])->name('api.proposals.status');
//     
//     // Invoice API endpoints
//     Route::apiResource('invoices', InvoiceController::class)->names([
//         'index' => 'api.invoices.index',
//         'store' => 'api.invoices.store',
//         'show' => 'api.invoices.show',
//         'update' => 'api.invoices.update',
//         'destroy' => 'api.invoices.destroy',
//     ]);
//     Route::post('invoices/{invoice}/send', [InvoiceController::class, 'send'])->name('api.invoices.send');
//     Route::patch('invoices/{invoice}/status', [InvoiceController::class, 'changeStatus'])->name('api.invoices.status');
//     
//     // Analytics endpoints (admin only)
//     Route::middleware('admin')->group(function () {
//         Route::get('/analytics/dashboard', function () {
//             $dashboardController = new \App\Http\Controllers\DashboardController();
//             $data = $dashboardController->index()->getData();
//             return response()->json($data);
//         });
//         
//         Route::get('/analytics/revenue', function () {
//             $revenue = \App\Models\Transaction::where('status', 'completed')
//                 ->selectRaw('DATE(created_at) as date, SUM(amount) as total')
//                 ->groupBy('date')
//                 ->orderBy('date', 'desc')
//                 ->take(30)
//                 ->get();
//             
//             return response()->json(['revenue' => $revenue]);
//         });
//     });
// });
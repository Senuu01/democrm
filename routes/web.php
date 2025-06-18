<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ProposalController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CustomLoginController;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect('/dashboard');
    }
    return view('welcome');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Profile routes
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Resource routes
    Route::resource('customers', CustomerController::class);
    Route::resource('proposals', ProposalController::class);
    Route::resource('invoices', InvoiceController::class);
    Route::resource('transactions', TransactionController::class)->only(['index', 'show']);
    
    // Invoice payment routes
    Route::post('/invoices/{invoice}/send', [InvoiceController::class, 'send'])->name('invoices.send');
    Route::get('/invoices/{invoice}/payment', [InvoiceController::class, 'payment'])->name('invoices.payment');
    Route::get('/pay/{invoice}', [InvoiceController::class, 'redirectToStripe'])->name('invoices.pay');
    Route::get('/invoices/{invoice}/payment/success', [InvoiceController::class, 'paymentSuccess'])->name('invoices.payment.success');
    Route::patch('/invoices/{invoice}/status', [InvoiceController::class, 'changeStatus'])->name('invoices.status');

    // Proposal status routes
    Route::post('/proposals/{proposal}/status', [ProposalController::class, 'updateStatus'])->name('proposals.updateStatus');
});

// Stripe webhook (no auth required)
Route::post('/webhook/stripe', [App\Http\Controllers\StripeWebhookController::class, 'handleWebhook'])->name('stripe.webhook');

Route::get('/login', [CustomLoginController::class, 'index'])->name('login');
Route::post('/login', [CustomLoginController::class, 'login']);

require __DIR__.'/auth.php';

<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Pay Invoice') }}
            </h2>
            <a href="{{ route('invoices.show', $invoice) }}" class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                Back to Invoice
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <!-- Display Success/Error Messages -->
            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    {{ session('success') }}
                </div>
            @endif
            
            @if(session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    {{ session('error') }}
                </div>
            @endif
            
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div class="text-center mb-8">
                        <h3 class="text-2xl font-bold text-gray-900 mb-2">Payment for Invoice #{{ $invoice->invoice_number }}</h3>
                        <p class="text-gray-600">Please review the details below and click "Pay Now" to proceed with payment.</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
                        <!-- Invoice Details -->
                        <div class="bg-gray-50 rounded-lg p-6">
                            <h4 class="text-lg font-semibold text-gray-900 mb-4">Invoice Details</h4>
                            <div class="space-y-3">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Invoice Number:</span>
                                    <span class="font-medium">{{ $invoice->invoice_number }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Issue Date:</span>
                                    <span class="font-medium">{{ $invoice->issue_date->format('F j, Y') }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Due Date:</span>
                                    <span class="font-medium">{{ $invoice->due_date->format('F j, Y') }}</span>
                                </div>
                                @if($invoice->notes)
                                    <div class="border-t pt-3">
                                        <span class="text-gray-600">Notes:</span>
                                        <p class="text-sm text-gray-700 mt-1">{{ $invoice->notes }}</p>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Customer Information -->
                        <div class="bg-gray-50 rounded-lg p-6">
                            <h4 class="text-lg font-semibold text-gray-900 mb-4">Customer Information</h4>
                            <div class="space-y-3">
                                <div>
                                    <span class="text-gray-600">Name:</span>
                                    <p class="font-medium">{{ $invoice->customer->name }}</p>
                                </div>
                                <div>
                                    <span class="text-gray-600">Company:</span>
                                    <p class="font-medium">{{ $invoice->customer->company_name }}</p>
                                </div>
                                <div>
                                    <span class="text-gray-600">Email:</span>
                                    <p class="font-medium">{{ $invoice->customer->email }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Summary -->
                    <div class="bg-indigo-50 rounded-lg p-6 mb-8">
                        <h4 class="text-lg font-semibold text-gray-900 mb-4">Payment Summary</h4>
                        <div class="space-y-3">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Subtotal:</span>
                                <span class="font-medium">${{ number_format($invoice->amount, 2) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Tax:</span>
                                <span class="font-medium">${{ number_format($invoice->tax_amount, 2) }}</span>
                            </div>
                            <div class="border-t pt-3">
                                <div class="flex justify-between">
                                    <span class="text-lg font-semibold text-gray-900">Total Amount:</span>
                                    <span class="text-2xl font-bold text-indigo-600">${{ number_format($invoice->total_amount, 2) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Button -->
                    <div class="text-center">
                        <a href="{{ route('invoices.pay', $invoice) }}" 
                           class="bg-green-600 hover:bg-green-700 text-white font-bold py-4 px-8 rounded-lg text-lg inline-flex items-center">
                            <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                            </svg>
                            Pay Now - ${{ number_format($invoice->total_amount, 2) }}
                        </a>
                        <p class="text-sm text-gray-500 mt-3">You will be redirected to Stripe for secure payment processing.</p>
                        <p class="text-xs text-green-600 mt-2">✓ Payment status will update automatically after completion</p>
                    </div>

                    <!-- Security Notice -->
                    <div class="mt-8 bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <div class="flex">
                            <svg class="w-5 h-5 text-blue-400 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                            </svg>
                            <div>
                                <h5 class="font-medium text-blue-900">Secure Payment</h5>
                                <p class="text-sm text-blue-700">Your payment information is encrypted and secure. We use Stripe for payment processing and never store your credit card details.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout> 
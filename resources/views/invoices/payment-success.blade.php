<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Payment Successful') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div class="text-center">
                        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100 mb-4">
                            <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                        
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Payment Successful!</h3>
                        <p class="text-gray-600 mb-6">Thank you for your payment. Your invoice has been marked as paid.</p>
                        
                        <div class="bg-gray-50 rounded-lg p-6 mb-6">
                            <h4 class="font-semibold text-gray-900 mb-4">Payment Details</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-left">
                                <div>
                                    <span class="text-sm font-medium text-gray-500">Invoice Number</span>
                                    <p class="mt-1">{{ $invoice->invoice_number }}</p>
                                </div>
                                <div>
                                    <span class="text-sm font-medium text-gray-500">Transaction Number</span>
                                    <p class="mt-1">{{ $transaction->transaction_number }}</p>
                                </div>
                                <div>
                                    <span class="text-sm font-medium text-gray-500">Amount Paid</span>
                                    <p class="mt-1 text-lg font-semibold text-green-600">${{ number_format($transaction->amount, 2) }}</p>
                                </div>
                                <div>
                                    <span class="text-sm font-medium text-gray-500">Payment Method</span>
                                    <p class="mt-1">{{ ucfirst($transaction->payment_method) }}</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex justify-center space-x-4">
                            <a href="{{ route('invoices.show', $invoice) }}" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded">
                                View Invoice
                            </a>
                            <a href="{{ route('dashboard') }}" class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                                Back to Dashboard
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout> 
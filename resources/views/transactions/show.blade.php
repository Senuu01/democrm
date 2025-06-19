<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Transaction Details') }}
            </h2>
            @if($transaction->status === 'completed')
                <form action="{{ route('transactions.refund', $transaction) }}" method="POST" class="inline">
                    @csrf
                    @method('POST')
                    <button type="submit" class="bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-2 px-4 rounded" onclick="return confirm('Are you sure you want to refund this transaction?')">
                        Refund Transaction
                    </button>
                </form>
            @endif
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Transaction Information -->
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Transaction Information</h3>
                            <div class="space-y-4">
                                <div>
                                    <span class="text-sm font-medium text-gray-500">Transaction Number</span>
                                    <p class="mt-1">{{ $transaction->transaction_number }}</p>
                                </div>
                                <div>
                                    <span class="text-sm font-medium text-gray-500">Status</span>
                                    <p class="mt-1">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                            @if($transaction->status === 'completed') bg-green-100 text-green-800
                                            @elseif($transaction->status === 'failed') bg-red-100 text-red-800
                                            @elseif($transaction->status === 'refunded') bg-yellow-100 text-yellow-800
                                            @else bg-gray-100 text-gray-800
                                            @endif">
                                            {{ ucfirst($transaction->status) }}
                                        </span>
                                    </p>
                                </div>
                                <div>
                                    <span class="text-sm font-medium text-gray-500">Amount</span>
                                    <p class="mt-1 text-2xl font-bold text-indigo-600">${{ number_format($transaction->amount, 2) }}</p>
                                </div>
                                <div>
                                    <span class="text-sm font-medium text-gray-500">Payment Method</span>
                                    <p class="mt-1">{{ ucfirst($transaction->payment_method) }}</p>
                                </div>
                                <div>
                                    <span class="text-sm font-medium text-gray-500">Date</span>
                                    <p class="mt-1">{{ $transaction->created_at->format('F j, Y H:i:s') }}</p>
                                </div>
                                @if($transaction->refund_id)
                                    <div>
                                        <span class="text-sm font-medium text-gray-500">Refund ID</span>
                                        <p class="mt-1">{{ $transaction->refund_id }}</p>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Customer Information -->
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Customer Information</h3>
                            <div class="space-y-4">
                                <div>
                                    <span class="text-sm font-medium text-gray-500">Name</span>
                                    <p class="mt-1">{{ $transaction->customer->name }}</p>
                                </div>
                                <div>
                                    <span class="text-sm font-medium text-gray-500">Company</span>
                                    <p class="mt-1">{{ $transaction->customer->company_name }}</p>
                                </div>
                                <div>
                                    <span class="text-sm font-medium text-gray-500">Email</span>
                                    <p class="mt-1">{{ $transaction->customer->email }}</p>
                                </div>
                                <div>
                                    <span class="text-sm font-medium text-gray-500">Phone</span>
                                    <p class="mt-1">{{ $transaction->customer->phone }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Invoice Information -->
                    @if($transaction->invoice)
                        <div class="mt-8">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Invoice Information</h3>
                            <div class="bg-gray-50 rounded-lg p-6">
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <span class="text-sm font-medium text-gray-500">Invoice Number</span>
                                        <p class="mt-1">
                                            <a href="{{ route('invoices.show', $transaction->invoice) }}" class="text-indigo-600 hover:text-indigo-900">
                                                {{ $transaction->invoice->invoice_number }}
                                            </a>
                                        </p>
                                    </div>
                                    <div>
                                        <span class="text-sm font-medium text-gray-500">Status</span>
                                        <p class="mt-1">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                                @if($transaction->invoice->status === 'paid') bg-green-100 text-green-800
                                                @elseif($transaction->invoice->status === 'overdue') bg-red-100 text-red-800
                                                @elseif($transaction->invoice->status === 'sent') bg-blue-100 text-blue-800
                                                @elseif($transaction->invoice->status === 'refunded') bg-yellow-100 text-yellow-800
                                                @else bg-gray-100 text-gray-800
                                                @endif">
                                                {{ ucfirst($transaction->invoice->status) }}
                                            </span>
                                        </p>
                                    </div>
                                    <div>
                                        <span class="text-sm font-medium text-gray-500">Issue Date</span>
                                        <p class="mt-1">{{ $transaction->invoice->issue_date->format('F j, Y') }}</p>
                                    </div>
                                    <div>
                                        <span class="text-sm font-medium text-gray-500">Due Date</span>
                                        <p class="mt-1">{{ $transaction->invoice->due_date->format('F j, Y') }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

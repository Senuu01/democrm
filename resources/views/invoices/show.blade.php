<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Invoice Details') }}
            </h2>
            <div class="flex space-x-2">
                @if(($invoice['status'] ?? 'draft') === 'draft')
                    <a href="{{ route('invoices.edit', $invoice['id']) }}" class="bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-2 px-4 rounded">
                        Edit Invoice
                    </a>
                    <form action="{{ route('invoices.send', $invoice['id']) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded">
                            Send Invoice
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Invoice Information -->
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Invoice Information</h3>
                            <div class="space-y-4">
                                <div>
                                    <span class="text-sm font-medium text-gray-500">Invoice Number</span>
                                    <p class="mt-1">{{ $invoice['invoice_number'] ?? 'N/A' }}</p>
                                </div>
                                <div>
                                    <span class="text-sm font-medium text-gray-500">Status</span>
                                    <p class="mt-1">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            @if(($invoice['status'] ?? 'draft') === 'paid') bg-green-100 text-green-800
                                            @elseif(($invoice['status'] ?? 'draft') === 'overdue') bg-red-100 text-red-800
                                            @elseif(($invoice['status'] ?? 'draft') === 'sent') bg-blue-100 text-blue-800
                                            @else bg-gray-100 text-gray-800
                                            @endif">
                                            {{ ucfirst($invoice['status'] ?? 'draft') }}
                                        </span>
                                    </p>
                                </div>
                                <div>
                                    <span class="text-sm font-medium text-gray-500">Issue Date</span>
                                    <p class="mt-1">{{ isset($invoice['issue_date']) ? \Carbon\Carbon::parse($invoice['issue_date'])->format('F j, Y') : 'N/A' }}</p>
                                </div>
                                <div>
                                    <span class="text-sm font-medium text-gray-500">Due Date</span>
                                    <p class="mt-1">{{ isset($invoice['due_date']) ? \Carbon\Carbon::parse($invoice['due_date'])->format('F j, Y') : 'N/A' }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Customer Information -->
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Customer Information</h3>
                            <div class="space-y-4">
                                <div>
                                    <span class="text-sm font-medium text-gray-500">Name</span>
                                    <p class="mt-1">{{ $invoice['customer']['name'] ?? 'N/A' }}</p>
                                </div>
                                <div>
                                    <span class="text-sm font-medium text-gray-500">Company</span>
                                    <p class="mt-1">{{ $invoice['customer']['company_name'] ?? $invoice['customer']['company'] ?? 'N/A' }}</p>
                                </div>
                                <div>
                                    <span class="text-sm font-medium text-gray-500">Email</span>
                                    <p class="mt-1">{{ $invoice['customer']['email'] ?? 'N/A' }}</p>
                                </div>
                                <div>
                                    <span class="text-sm font-medium text-gray-500">Phone</span>
                                    <p class="mt-1">{{ $invoice['customer']['phone'] ?? 'N/A' }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Invoice Details -->
                    <div class="mt-8">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Invoice Details</h3>
                        <div class="bg-gray-50 rounded-lg p-6">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <span class="text-sm font-medium text-gray-500">Subtotal</span>
                                    <p class="mt-1 text-lg">${{ number_format($invoice['amount'] ?? 0, 2) }}</p>
                                </div>
                                <div>
                                    <span class="text-sm font-medium text-gray-500">Tax Amount</span>
                                    <p class="mt-1 text-lg">${{ number_format($invoice['tax_amount'] ?? 0, 2) }}</p>
                                </div>
                                <div class="col-span-2 border-t border-gray-200 pt-4 mt-4">
                                    <span class="text-sm font-medium text-gray-500">Total Amount</span>
                                    <p class="mt-1 text-2xl font-bold text-indigo-600">${{ number_format(($invoice['amount'] ?? 0) + ($invoice['tax_amount'] ?? 0), 2) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if($invoice['notes'] ?? '')
                        <div class="mt-8">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Notes</h3>
                            <div class="bg-gray-50 rounded-lg p-6">
                                <p class="text-gray-700">{{ $invoice['notes'] }}</p>
                            </div>
                        </div>
                    @endif

                    <!-- Payment History -->
                    @if(isset($invoice['transactions']) && count($invoice['transactions']) > 0)
                        <div class="mt-8">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Payment History</h3>
                            <div class="bg-white shadow overflow-hidden sm:rounded-md">
                                <ul class="divide-y divide-gray-200">
                                    @foreach($invoice['transactions'] as $transaction)
                                        <li class="px-6 py-4">
                                            <div class="flex items-center justify-between">
                                                <div>
                                                    <p class="text-sm font-medium text-gray-900">
                                                        Transaction #{{ $transaction['id'] ?? 'N/A' }}
                                                    </p>
                                                    <p class="text-sm text-gray-500">
                                                        {{ \Carbon\Carbon::parse($transaction['created_at'] ?? now())->format('F j, Y H:i:s') }}
                                                    </p>
                                                </div>
                                                <div class="text-right">
                                                    <p class="text-sm font-medium text-gray-900">
                                                        ${{ number_format($transaction['amount'] ?? 0, 2) }}
                                                    </p>
                                                    <p class="text-sm text-gray-500">
                                                        {{ ucfirst($transaction['status'] ?? 'pending') }}
                                                    </p>
                                                </div>
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout> 
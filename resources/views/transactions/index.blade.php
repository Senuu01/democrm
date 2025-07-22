<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Transactions') }}
            </h2>
            <div class="flex space-x-4">
                <form action="{{ route('transactions.syncAllPending') }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        Sync All Pending
                    </button>
                </form>
                <button onclick="window.location.reload()" class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    Refresh
                </button>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    @if(session('success'))
                        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                            <span class="block sm:inline">{{ session('success') }}</span>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                            <span class="block sm:inline">{{ session('error') }}</span>
                        </div>
                    @endif

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Transaction #</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Invoice</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payment Method</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($transactions as $transaction)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            {{ $transaction['id'] ?? 'N/A' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $transaction['customer']['name'] ?? 'N/A' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            @if($transaction['invoice'])
                                                <a href="{{ route('invoices.show', $transaction['invoice']['id']) }}" class="text-indigo-600 hover:text-indigo-900">
                                                    {{ $transaction['invoice']['invoice_number'] ?? 'N/A' }}
                                                </a>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            ${{ number_format($transaction['amount'] ?? 0, 2) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ ucfirst($transaction['payment_method'] ?? 'N/A') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center space-x-2">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                    @if(($transaction['status'] ?? 'pending') === 'completed') bg-green-100 text-green-800
                                                    @elseif(($transaction['status'] ?? 'pending') === 'failed') bg-red-100 text-red-800
                                                    @elseif(($transaction['status'] ?? 'pending') === 'expired') bg-orange-100 text-orange-800
                                                    @elseif(($transaction['status'] ?? 'pending') === 'refunded') bg-yellow-100 text-yellow-800
                                                    @elseif(($transaction['status'] ?? 'pending') === 'pending') bg-blue-100 text-blue-800
                                                    @elseif(($transaction['status'] ?? 'pending') === 'cancelled') bg-gray-100 text-gray-800
                                                    @else bg-gray-100 text-gray-800
                                                    @endif">
                                                    @if(($transaction['status'] ?? 'pending') === 'completed')
                                                        ✓ Payment Successful
                                                    @elseif(($transaction['status'] ?? 'pending') === 'failed')
                                                        ✗ Payment Failed
                                                    @elseif(($transaction['status'] ?? 'pending') === 'expired')
                                                        ⏰ Payment Expired
                                                    @elseif(($transaction['status'] ?? 'pending') === 'refunded')
                                                        ↩ Refunded
                                                    @elseif(($transaction['status'] ?? 'pending') === 'pending')
                                                        ⏳ Pending
                                                    @elseif(($transaction['status'] ?? 'pending') === 'cancelled')
                                                        ❌ Payment Cancelled
                                                    @else
                                                        {{ ucfirst($transaction['status'] ?? 'pending') }}
                                                    @endif
                                                </span>
                                                @if(($transaction['stripe_session_id'] ?? '') || ($transaction['stripe_payment_intent_id'] ?? ''))
                                                    <span class="text-xs text-gray-400" title="Connected to Stripe">🔗</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ \Carbon\Carbon::parse($transaction['created_at'] ?? now())->format('M d, Y H:i:s') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <div class="flex space-x-2">
                                                <a href="{{ route('transactions.show', $transaction['id']) }}" class="text-indigo-600 hover:text-indigo-900">View</a>
                                                
                                                @if(in_array($transaction['status'] ?? 'pending', ['pending', 'failed']) && (($transaction['stripe_session_id'] ?? '') || ($transaction['stripe_payment_intent_id'] ?? '')))
                                                    <form action="{{ route('transactions.sync', $transaction['id']) }}" method="POST" class="inline">
                                                        @csrf
                                                        <button type="submit" class="text-blue-600 hover:text-blue-900" title="Sync with Stripe">Sync</button>
                                                    </form>
                                                @endif
                                                
                                                @if(($transaction['status'] ?? 'pending') === 'completed')
                                                    <form action="{{ route('transactions.refund', $transaction['id']) }}" method="POST" class="inline">
                                                        @csrf
                                                        @method('POST')
                                                        <button type="submit" class="text-yellow-600 hover:text-yellow-900" onclick="return confirm('Are you sure you want to refund this transaction?')">Refund</button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if(empty($transactions))
                        <div class="text-center py-8">
                            <p class="text-gray-500">No transactions found.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout> 
<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Transaction Details') }}
            </h2>
            <div class="flex space-x-4">
                @if(in_array($transaction['status'] ?? 'pending', ['pending', 'failed']) && (($transaction['stripe_session_id'] ?? '') || ($transaction['stripe_payment_intent_id'] ?? '')))
                    <button id="checkStatusBtn" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        Check Status
                    </button>
                    
                    <form action="{{ route('transactions.sync', $transaction['id']) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            Sync with Stripe
                        </button>
                    </form>
                @endif
                
                @if(($transaction['status'] ?? 'pending') === 'completed')
                    <form action="{{ route('transactions.refund', $transaction['id']) }}" method="POST" class="inline">
                        @csrf
                        @method('POST')
                        <button type="submit" class="bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-2 px-4 rounded" onclick="return confirm('Are you sure you want to refund this transaction?')">
                            Refund Transaction
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
                        <!-- Transaction Information -->
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Transaction Information</h3>
                            <div class="space-y-4">
                                <div>
                                    <span class="text-sm font-medium text-gray-500">Transaction ID</span>
                                    <p class="mt-1">{{ $transaction['id'] ?? 'N/A' }}</p>
                                </div>
                                <div>
                                    <span class="text-sm font-medium text-gray-500">Status</span>
                                    <p class="mt-1">
                                        <span id="statusBadge" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
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
                                        <span id="lastUpdated" class="ml-2 text-xs text-gray-400">
                                            Last updated: {{ \Carbon\Carbon::parse($transaction['updated_at'] ?? now())->format('M d, Y H:i:s') }}
                                        </span>
                                    </p>
                                </div>
                                <div>
                                    <span class="text-sm font-medium text-gray-500">Amount</span>
                                    <p class="mt-1 text-2xl font-bold text-indigo-600">${{ number_format($transaction['amount'] ?? 0, 2) }}</p>
                                </div>
                                <div>
                                    <span class="text-sm font-medium text-gray-500">Payment Method</span>
                                    <p class="mt-1">{{ ucfirst($transaction['payment_method'] ?? 'N/A') }}</p>
                                </div>
                                <div>
                                    <span class="text-sm font-medium text-gray-500">Date</span>
                                    <p class="mt-1">{{ \Carbon\Carbon::parse($transaction['created_at'] ?? now())->format('F j, Y H:i:s') }}</p>
                                </div>
                                @if($transaction->stripe_session_id)
                                    <div>
                                        <span class="text-sm font-medium text-gray-500">Stripe Session ID</span>
                                        <p class="mt-1 text-xs font-mono bg-gray-100 p-1 rounded">{{ $transaction->stripe_session_id }}</p>
                                    </div>
                                @endif
                                @if($transaction->stripe_payment_intent_id)
                                    <div>
                                        <span class="text-sm font-medium text-gray-500">Stripe Payment Intent ID</span>
                                        <p class="mt-1 text-xs font-mono bg-gray-100 p-1 rounded">{{ $transaction->stripe_payment_intent_id }}</p>
                                    </div>
                                @endif
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

    @push('scripts')
    <script>
        // Real-time status checking
        let statusCheckInterval;
        let isChecking = false;

        document.addEventListener('DOMContentLoaded', function() {
            const checkStatusBtn = document.getElementById('checkStatusBtn');
            
            if (checkStatusBtn) {
                checkStatusBtn.addEventListener('click', function() {
                    if (isChecking) {
                        stopStatusCheck();
                    } else {
                        startStatusCheck();
                    }
                });
                
                // Auto-check status for pending transactions
                @if($transaction->status === 'pending')
                    setTimeout(() => {
                        startStatusCheck();
                    }, 2000); // Start checking after 2 seconds
                @endif
            }
        });

        function startStatusCheck() {
            isChecking = true;
            const btn = document.getElementById('checkStatusBtn');
            btn.innerHTML = `
                <svg class="w-4 h-4 mr-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                Stop Checking
            `;
            btn.classList.remove('bg-blue-600', 'hover:bg-blue-700');
            btn.classList.add('bg-red-600', 'hover:bg-red-700');
            
            // Check immediately
            checkTransactionStatus();
            
            // Set up interval to check every 10 seconds
            statusCheckInterval = setInterval(checkTransactionStatus, 10000);
        }

        function stopStatusCheck() {
            isChecking = false;
            const btn = document.getElementById('checkStatusBtn');
            btn.innerHTML = `
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                Check Status
            `;
            btn.classList.remove('bg-red-600', 'hover:bg-red-700');
            btn.classList.add('bg-blue-600', 'hover:bg-blue-700');
            
            if (statusCheckInterval) {
                clearInterval(statusCheckInterval);
            }
        }

        function checkTransactionStatus() {
            fetch('{{ route("transactions.getStatus", $transaction) }}')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        updateStatusDisplay(data.status, data.status_display, data.updated_at);
                        
                        // If status changed to completed, failed, or expired, stop checking
                        if (['completed', 'failed', 'expired', 'refunded', 'cancelled'].includes(data.status)) {
                            stopStatusCheck();
                            
                            // Reload page after a short delay to update buttons and other elements
                            setTimeout(() => {
                                window.location.reload();
                            }, 2000);
                        }
                    } else {
                        console.error('Status check failed:', data.error);
                    }
                })
                .catch(error => {
                    console.error('Error checking status:', error);
                });
        }

        function updateStatusDisplay(status, statusDisplay, updatedAt) {
            const statusBadge = document.getElementById('statusBadge');
            const lastUpdated = document.getElementById('lastUpdated');
            
            if (statusBadge) {
                // Remove all status classes
                statusBadge.className = 'px-2 inline-flex text-xs leading-5 font-semibold rounded-full';
                
                // Add appropriate status class
                switch(status) {
                    case 'completed':
                        statusBadge.classList.add('bg-green-100', 'text-green-800');
                        break;
                    case 'failed':
                        statusBadge.classList.add('bg-red-100', 'text-red-800');
                        break;
                    case 'expired':
                        statusBadge.classList.add('bg-orange-100', 'text-orange-800');
                        break;
                    case 'refunded':
                        statusBadge.classList.add('bg-yellow-100', 'text-yellow-800');
                        break;
                    case 'pending':
                        statusBadge.classList.add('bg-blue-100', 'text-blue-800');
                        break;
                    case 'cancelled':
                        statusBadge.classList.add('bg-gray-100', 'text-gray-800');
                        break;
                    default:
                        statusBadge.classList.add('bg-gray-100', 'text-gray-800');
                }
                
                statusBadge.textContent = statusDisplay;
            }
            
            if (lastUpdated) {
                lastUpdated.textContent = 'Last updated: ' + updatedAt;
            }
        }
    </script>
    @endpush
</x-app-layout>

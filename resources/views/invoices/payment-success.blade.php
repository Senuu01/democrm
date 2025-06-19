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
                                    <p class="mt-1" id="transactionNumber">{{ isset($transaction) ? $transaction->transaction_number : 'Processing...' }}</p>
                                </div>
                                <div>
                                    <span class="text-sm font-medium text-gray-500">Amount Paid</span>
                                    <p class="mt-1 text-lg font-semibold text-green-600">${{ number_format(isset($transaction) ? $transaction->amount : $invoice->total_amount, 2) }}</p>
                                </div>
                                <div>
                                    <span class="text-sm font-medium text-gray-500">Payment Method</span>
                                    <p class="mt-1">{{ isset($transaction) ? ucfirst($transaction->payment_method) : 'Stripe' }}</p>
                                </div>
                                <div>
                                    <span class="text-sm font-medium text-gray-500">Status</span>
                                    <p class="mt-1" id="paymentStatus">
                                        @if(isset($transaction))
                                            @if($transaction->status === 'completed')
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                                    ✓ Payment Successful
                                                </span>
                                            @elseif($transaction->status === 'pending')
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                                    ⏳ Processing...
                                                </span>
                                            @else
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">
                                                    {{ ucfirst($transaction->status) }}
                                                </span>
                                            @endif
                                        @else
                                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                                ⏳ Processing...
                                            </span>
                                        @endif
                                    </p>
                                </div>
                            </div>
                            @if(!isset($transaction) || (isset($transaction) && $transaction->status === 'pending'))
                                <div class="mt-4 p-3 bg-blue-50 border border-blue-200 rounded-md" id="processingNotice">
                                    <p class="text-sm text-blue-700">
                                        <svg class="animate-spin -ml-1 mr-3 h-4 w-4 text-blue-700 inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Payment is being processed. Your transaction status will update automatically.
                                    </p>
                                </div>
                            @endif
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

    @push('scripts')
    <script>
        // Auto-check payment status for pending transactions
        let statusCheckInterval;
        let checkAttempts = 0;
        const maxAttempts = 20; // Check for up to 20 times (4 minutes)

        document.addEventListener('DOMContentLoaded', function() {
            // Only start checking if we have a transaction that's pending or no transaction yet
            @if(!isset($transaction) || (isset($transaction) && $transaction->status === 'pending'))
                setTimeout(() => {
                    startStatusCheck();
                }, 2000); // Wait 2 seconds before starting
            @endif
        });

        function startStatusCheck() {
            statusCheckInterval = setInterval(checkPaymentStatus, 12000); // Check every 12 seconds
        }

        function stopStatusCheck() {
            if (statusCheckInterval) {
                clearInterval(statusCheckInterval);
            }
        }

        function checkPaymentStatus() {
            checkAttempts++;
            
            // Stop checking after max attempts
            if (checkAttempts >= maxAttempts) {
                stopStatusCheck();
                return;
            }

            // Get session ID from URL
            const urlParams = new URLSearchParams(window.location.search);
            const sessionId = urlParams.get('session_id');
            
            if (!sessionId) {
                stopStatusCheck();
                return;
            }

            // Call a new endpoint to check payment status
            fetch(`/invoices/{{ $invoice->id }}/check-payment-status?session_id=${sessionId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        updatePaymentStatus(data.transaction);
                        
                        // If payment is completed, stop checking
                        if (data.transaction.status === 'completed') {
                            stopStatusCheck();
                            hideProcessingNotice();
                            showSuccessMessage();
                        } else if (data.transaction.status === 'failed') {
                            stopStatusCheck();
                            showFailureMessage();
                        }
                    }
                })
                .catch(error => {
                    console.error('Error checking payment status:', error);
                });
        }

        function updatePaymentStatus(transaction) {
            // Update transaction number
            const transactionNumberEl = document.getElementById('transactionNumber');
            if (transactionNumberEl) {
                transactionNumberEl.textContent = transaction.transaction_number;
            }

            // Update status badge
            const statusEl = document.getElementById('paymentStatus');
            if (statusEl) {
                let statusHtml = '';
                switch(transaction.status) {
                    case 'completed':
                        statusHtml = '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">✓ Payment Successful</span>';
                        break;
                    case 'failed':
                        statusHtml = '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">✗ Payment Failed</span>';
                        break;
                    case 'pending':
                        statusHtml = '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">⏳ Processing...</span>';
                        break;
                    default:
                        statusHtml = `<span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">${transaction.status}</span>`;
                }
                statusEl.innerHTML = statusHtml;
            }
        }

        function hideProcessingNotice() {
            const notice = document.getElementById('processingNotice');
            if (notice) {
                notice.style.display = 'none';
            }
        }

        function showSuccessMessage() {
            // Create success notification
            const successNotice = document.createElement('div');
            successNotice.className = 'mt-4 p-3 bg-green-50 border border-green-200 rounded-md';
            successNotice.innerHTML = `
                <p class="text-sm text-green-700">
                    <svg class="h-4 w-4 text-green-700 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Payment confirmed! Your invoice has been marked as paid.
                </p>
            `;
            
            // Insert after the payment details grid
            const paymentGrid = document.querySelector('.grid');
            paymentGrid.parentNode.insertBefore(successNotice, paymentGrid.nextSibling);
        }

        function showFailureMessage() {
            const failureNotice = document.createElement('div');
            failureNotice.className = 'mt-4 p-3 bg-red-50 border border-red-200 rounded-md';
            failureNotice.innerHTML = `
                <p class="text-sm text-red-700">
                    <svg class="h-4 w-4 text-red-700 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Payment processing failed. Please contact support if you believe this is an error.
                </p>
            `;
            
            hideProcessingNotice();
            const paymentGrid = document.querySelector('.grid');
            paymentGrid.parentNode.insertBefore(failureNotice, paymentGrid.nextSibling);
        }
    </script>
    @endpush
</x-app-layout> 
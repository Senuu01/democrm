<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Pay Invoice') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Invoice Summary -->
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Invoice Summary</h3>
                            <div class="bg-gray-50 rounded-lg p-6">
                                <div class="space-y-4">
                                    <div>
                                        <span class="text-sm font-medium text-gray-500">Invoice Number</span>
                                        <p class="mt-1">{{ $invoice->invoice_number }}</p>
                                    </div>
                                    <div>
                                        <span class="text-sm font-medium text-gray-500">Customer</span>
                                        <p class="mt-1">{{ $invoice->customer->name }}</p>
                                    </div>
                                    <div>
                                        <span class="text-sm font-medium text-gray-500">Due Date</span>
                                        <p class="mt-1">{{ $invoice->due_date->format('F j, Y') }}</p>
                                    </div>
                                    <div class="border-t border-gray-200 pt-4 mt-4">
                                        <span class="text-sm font-medium text-gray-500">Total Amount</span>
                                        <p class="mt-1 text-2xl font-bold text-indigo-600">${{ number_format($invoice->total_amount, 2) }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Payment Form -->
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Payment Details</h3>
                            <form id="payment-form" class="space-y-6">
                                <div>
                                    <label for="card-element" class="block text-sm font-medium text-gray-700">
                                        Credit or debit card
                                    </label>
                                    <div class="mt-1">
                                        <div id="card-element" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                            <!-- Stripe Card Element will be inserted here -->
                                        </div>
                                    </div>
                                    <div id="card-errors" class="mt-2 text-sm text-red-600" role="alert"></div>
                                </div>

                                <div class="flex items-center justify-end">
                                    <button type="submit" id="submit-button" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                                        Pay ${{ number_format($invoice->total_amount, 2) }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="https://js.stripe.com/v3/"></script>
    <script>
        // Initialize Stripe
        const stripe = Stripe('{{ config('services.stripe.key') }}');
        const elements = stripe.elements();

        // Create card element
        const card = elements.create('card');
        card.mount('#card-element');

        // Handle form submission
        const form = document.getElementById('payment-form');
        const submitButton = document.getElementById('submit-button');

        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            submitButton.disabled = true;
            submitButton.textContent = 'Processing...';

            try {
                const { paymentIntent, error } = await stripe.confirmCardPayment('{{ $clientSecret }}', {
                    payment_method: {
                        card: card,
                        billing_details: {
                            name: '{{ $invoice->customer->name }}',
                            email: '{{ $invoice->customer->email }}',
                        },
                    },
                });

                if (error) {
                    const errorElement = document.getElementById('card-errors');
                    errorElement.textContent = error.message;
                    submitButton.disabled = false;
                    submitButton.textContent = 'Pay ${{ number_format($invoice->total_amount, 2) }}';
                } else {
                    // Payment successful
                    window.location.href = '{{ route('invoices.show', $invoice) }}';
                }
            } catch (error) {
                const errorElement = document.getElementById('card-errors');
                errorElement.textContent = 'An unexpected error occurred.';
                submitButton.disabled = false;
                submitButton.textContent = 'Pay ${{ number_format($invoice->total_amount, 2) }}';
            }
        });

        // Handle card element errors
        card.addEventListener('change', ({error}) => {
            const displayError = document.getElementById('card-errors');
            if (error) {
                displayError.textContent = error.message;
            } else {
                displayError.textContent = '';
            }
        });
    </script>
    @endpush
</x-app-layout> 
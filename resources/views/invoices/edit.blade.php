<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Invoice') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <form action="{{ route('invoices.update', $invoice) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-4">
                            <x-input-label for="customer_id" :value="__('Customer')" />
                            <select name="customer_id" id="customer_id" class="block mt-1 w-full rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" required>
                                <option value="">Select a customer</option>
                                @foreach($customers as $customer)
                                    <option value="{{ $customer['id'] }}" {{ old('customer_id', $invoice['customer_id'] ?? $invoice->customer_id) == $customer['id'] ? 'selected' : '' }}>
                                        {{ $customer['name'] }} ({{ $customer['company_name'] ?? 'N/A' }})
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('customer_id')" class="mt-2" />
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="mb-4">
                                <x-input-label for="amount" :value="__('Amount')" />
                                <x-text-input id="amount" type="number" step="0.01" name="amount" :value="old('amount', $invoice->amount)" class="block mt-1 w-full" required />
                                <x-input-error :messages="$errors->get('amount')" class="mt-2" />
                            </div>

                            <div class="mb-4">
                                <x-input-label for="tax_amount" :value="__('Tax Amount')" />
                                <x-text-input id="tax_amount" type="number" step="0.01" name="tax_amount" :value="old('tax_amount', $invoice->tax_amount)" class="block mt-1 w-full" required />
                                <x-input-error :messages="$errors->get('tax_amount')" class="mt-2" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="mb-4">
                                <x-input-label for="issue_date" :value="__('Issue Date')" />
                                <x-text-input id="issue_date" type="date" name="issue_date" :value="old('issue_date', optional($invoice->issue_date)->format('Y-m-d'))" class="block mt-1 w-full" required />
                                <x-input-error :messages="$errors->get('issue_date')" class="mt-2" />
                            </div>

                            <div class="mb-4">
                                <x-input-label for="due_date" :value="__('Due Date')" />
                                <x-text-input id="due_date" type="date" name="due_date" :value="old('due_date', optional($invoice->due_date)->format('Y-m-d'))" class="block mt-1 w-full" required />
                                <x-input-error :messages="$errors->get('due_date')" class="mt-2" />
                            </div>
                        </div>

                        <div class="mb-4">
                            <x-input-label for="notes" :value="__('Notes')" />
                            <textarea name="notes" id="notes" rows="4" class="block mt-1 w-full rounded-md shadow-sm border-gray-300 focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">{{ old('notes', $invoice->notes) }}</textarea>
                            <x-input-error :messages="$errors->get('notes')" class="mt-2" />
                        </div>

                        <div class="flex items-center justify-end mt-4">
                            <a href="{{ route('invoices.index') }}" class="text-gray-600 hover:text-gray-900 mr-4">Cancel</a>
                            <x-primary-button>
                                {{ __('Update Invoice') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout> 
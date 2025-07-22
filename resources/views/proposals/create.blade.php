<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Proposal - Connectly CRM</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen">
        <!-- Header -->
        <header class="bg-white shadow">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <a href="{{ route('proposals.index') }}" class="text-xl font-semibold text-gray-900 hover:text-blue-600">
                            <i class="fas fa-arrow-left mr-2"></i>Back to Proposals
                        </a>
                    </div>
                    <div class="flex items-center space-x-4">
                        <span class="text-gray-700">{{ session('user_data.name', 'User') }}</span>
                        <form method="POST" action="{{ route('auth.logout') }}" class="inline">
                            @csrf
                            <button type="submit" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded">
                                <i class="fas fa-sign-out-alt mr-1"></i>Logout
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="max-w-4xl mx-auto py-6 px-4 sm:px-6 lg:px-8" x-data="proposalForm()">
            <!-- Page Header -->
            <div class="mb-8">
                <h1 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                    <i class="fas fa-file-alt text-blue-500 mr-3"></i>Create New Proposal
                </h1>
                <p class="mt-1 text-sm text-gray-500">Create a professional business proposal for your customer</p>
            </div>

            <!-- Alerts -->
            @if($errors->any())
                <div class="bg-red-50 border-l-4 border-red-400 p-4 mb-6" x-data="{ show: true }" x-show="show">
                    <div class="flex justify-between">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-exclamation-circle text-red-400"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-red-700 font-medium">Please correct the following errors:</p>
                                <ul class="mt-2 text-sm text-red-600 list-disc list-inside">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                        <button @click="show = false" class="text-red-400 hover:text-red-600">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            @endif

            <!-- Proposal Form -->
            <form method="POST" action="{{ route('proposals.store') }}" class="space-y-8">
                @csrf

                <!-- Basic Information -->
                <div class="bg-white shadow rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">
                        <i class="fas fa-info-circle mr-2 text-blue-500"></i>Basic Information
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="customer_id" class="block text-sm font-medium text-gray-700">
                                Customer <span class="text-red-500">*</span>
                            </label>
                            <select name="customer_id" id="customer_id" required
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('customer_id') border-red-300 @enderror">
                                <option value="">Select a customer</option>
                                @foreach($customers as $customer)
                                    <option value="{{ $customer['id'] }}" {{ old('customer_id', $preselectedCustomer) == $customer['id'] ? 'selected' : '' }}>
                                        {{ $customer['name'] }} {{ $customer['company'] ? '(' . $customer['company'] . ')' : '' }}
                                    </option>
                                @endforeach
                            </select>
                            @error('customer_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="valid_until" class="block text-sm font-medium text-gray-700">
                                Valid Until <span class="text-red-500">*</span>
                            </label>
                            <input type="date" name="valid_until" id="valid_until" value="{{ old('valid_until', date('Y-m-d', strtotime('+30 days'))) }}" required
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('valid_until') border-red-300 @enderror">
                            @error('valid_until')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="md:col-span-2">
                            <label for="title" class="block text-sm font-medium text-gray-700">
                                Proposal Title <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="title" id="title" value="{{ old('title') }}" required
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('title') border-red-300 @enderror"
                                   placeholder="Website Development Project">
                            @error('title')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="md:col-span-2">
                            <label for="description" class="block text-sm font-medium text-gray-700">
                                Description
                            </label>
                            <textarea name="description" id="description" rows="3"
                                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('description') border-red-300 @enderror"
                                      placeholder="Brief description of the proposal...">{{ old('description') }}</textarea>
                            @error('description')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Line Items -->
                <div class="bg-white shadow rounded-lg p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-medium text-gray-900">
                            <i class="fas fa-list mr-2 text-blue-500"></i>Line Items
                        </h3>
                        <button type="button" @click="addItem()" class="inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                            <i class="fas fa-plus mr-2"></i>Add Item
                        </button>
                    </div>

                    <div class="space-y-4">
                        <template x-for="(item, index) in items" :key="index">
                            <div class="border border-gray-200 rounded-lg p-4">
                                <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                                    <div class="md:col-span-5">
                                        <label :for="'item_description_' + index" class="block text-sm font-medium text-gray-700">
                                            Description <span class="text-red-500">*</span>
                                        </label>
                                        <input type="text" :name="'items[' + index + '][description]'" :id="'item_description_' + index" 
                                               x-model="item.description" required
                                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                               placeholder="Service or product description">
                                    </div>
                                    <div class="md:col-span-2">
                                        <label :for="'item_quantity_' + index" class="block text-sm font-medium text-gray-700">
                                            Quantity <span class="text-red-500">*</span>
                                        </label>
                                        <input type="number" :name="'items[' + index + '][quantity]'" :id="'item_quantity_' + index" 
                                               x-model="item.quantity" @input="calculateTotals()" min="0.01" step="0.01" required
                                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                    </div>
                                    <div class="md:col-span-2">
                                        <label :for="'item_rate_' + index" class="block text-sm font-medium text-gray-700">
                                            Rate ($) <span class="text-red-500">*</span>
                                        </label>
                                        <input type="number" :name="'items[' + index + '][rate]'" :id="'item_rate_' + index" 
                                               x-model="item.rate" @input="calculateTotals()" min="0" step="0.01" required
                                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                    </div>
                                    <div class="md:col-span-2">
                                        <label class="block text-sm font-medium text-gray-700">Total</label>
                                        <div class="mt-1 block w-full px-3 py-2 bg-gray-50 border border-gray-300 rounded-md text-sm font-medium">
                                            $<span x-text="(item.quantity * item.rate).toFixed(2)">0.00</span>
                                        </div>
                                    </div>
                                    <div class="md:col-span-1">
                                        <button type="button" @click="removeItem(index)" class="w-full inline-flex justify-center items-center px-3 py-2 border border-red-300 text-sm font-medium rounded-md text-red-700 bg-white hover:bg-red-50">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <div x-show="items.length === 0" class="text-center py-8 border-2 border-dashed border-gray-300 rounded-lg">
                            <i class="fas fa-list text-4xl text-gray-400 mb-4"></i>
                            <h3 class="text-sm font-medium text-gray-900">No items added yet</h3>
                            <p class="mt-1 text-sm text-gray-500">Add your first line item to get started.</p>
                            <button type="button" @click="addItem()" class="mt-4 inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                                <i class="fas fa-plus mr-2"></i>Add Item
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Financial Details -->
                <div class="bg-white shadow rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">
                        <i class="fas fa-calculator mr-2 text-blue-500"></i>Financial Details
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="discount_amount" class="block text-sm font-medium text-gray-700">
                                Discount Amount ($)
                            </label>
                            <input type="number" name="discount_amount" id="discount_amount" x-model="discountAmount" @input="calculateTotals()" 
                                   value="{{ old('discount_amount', 0) }}" min="0" step="0.01"
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('discount_amount') border-red-300 @enderror">
                            @error('discount_amount')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="tax_rate" class="block text-sm font-medium text-gray-700">
                                Tax Rate (%)
                            </label>
                            <input type="number" name="tax_rate" id="tax_rate" x-model="taxRate" @input="calculateTotals()" 
                                   value="{{ old('tax_rate', 0) }}" min="0" max="100" step="0.01"
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('tax_rate') border-red-300 @enderror">
                            @error('tax_rate')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Totals Summary -->
                    <div class="mt-6 bg-gray-50 rounded-lg p-4">
                        <div class="space-y-2">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Subtotal:</span>
                                <span class="font-medium">$<span x-text="subtotal.toFixed(2)">0.00</span></span>
                            </div>
                            <div class="flex justify-between text-sm" x-show="discountAmount > 0">
                                <span class="text-gray-600">Discount:</span>
                                <span class="font-medium text-red-600">-$<span x-text="parseFloat(discountAmount || 0).toFixed(2)">0.00</span></span>
                            </div>
                            <div class="flex justify-between text-sm" x-show="taxAmount > 0">
                                <span class="text-gray-600">Tax (<span x-text="taxRate">0</span>%):</span>
                                <span class="font-medium">$<span x-text="taxAmount.toFixed(2)">0.00</span></span>
                            </div>
                            <div class="border-t border-gray-200 pt-2 flex justify-between">
                                <span class="text-lg font-medium text-gray-900">Total:</span>
                                <span class="text-lg font-bold text-blue-600">$<span x-text="totalAmount.toFixed(2)">0.00</span></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Terms and Notes -->
                <div class="bg-white shadow rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">
                        <i class="fas fa-file-contract mr-2 text-blue-500"></i>Terms & Notes
                    </h3>
                    <div class="space-y-6">
                        <div>
                            <label for="terms_conditions" class="block text-sm font-medium text-gray-700">
                                Terms & Conditions
                            </label>
                            <textarea name="terms_conditions" id="terms_conditions" rows="4"
                                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('terms_conditions') border-red-300 @enderror"
                                      placeholder="Payment terms, delivery conditions, etc...">{{ old('terms_conditions') }}</textarea>
                            @error('terms_conditions')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="notes" class="block text-sm font-medium text-gray-700">
                                Internal Notes
                            </label>
                            <textarea name="notes" id="notes" rows="3"
                                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 @error('notes') border-red-300 @enderror"
                                      placeholder="Internal notes (not visible to customer)...">{{ old('notes') }}</textarea>
                            @error('notes')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="bg-white shadow rounded-lg p-6">
                    <div class="flex items-center justify-end space-x-3">
                        <a href="{{ route('proposals.index') }}" 
                           class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            <i class="fas fa-times mr-2"></i>Cancel
                        </a>
                        <button type="submit" 
                                class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <i class="fas fa-save mr-2"></i>Create Proposal
                        </button>
                    </div>
                </div>
            </form>
        </main>
    </div>

    <script>
        function proposalForm() {
            return {
                items: [
                    { description: '', quantity: 1, rate: 0 }
                ],
                discountAmount: 0,
                taxRate: 0,
                subtotal: 0,
                taxAmount: 0,
                totalAmount: 0,

                init() {
                    this.calculateTotals();
                },

                addItem() {
                    this.items.push({ description: '', quantity: 1, rate: 0 });
                },

                removeItem(index) {
                    if (this.items.length > 1) {
                        this.items.splice(index, 1);
                        this.calculateTotals();
                    }
                },

                calculateTotals() {
                    this.subtotal = this.items.reduce((sum, item) => {
                        return sum + (parseFloat(item.quantity || 0) * parseFloat(item.rate || 0));
                    }, 0);

                    const discount = parseFloat(this.discountAmount || 0);
                    const taxableAmount = this.subtotal - discount;
                    this.taxAmount = taxableAmount * (parseFloat(this.taxRate || 0) / 100);
                    this.totalAmount = taxableAmount + this.taxAmount;
                }
            }
        }
    </script>
</body>
</html> 
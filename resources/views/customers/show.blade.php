<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $customer['name'] ?? 'Customer' }} - Connectly CRM</title>
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
                        <a href="{{ route('customers.index') }}" class="text-xl font-semibold text-gray-900 hover:text-blue-600">
                            <i class="fas fa-arrow-left mr-2"></i>Back to Customers
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
        <main class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <!-- Page Header -->
            <div class="md:flex md:items-center md:justify-between mb-8">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 h-12 w-12">
                            <div class="h-12 w-12 rounded-full bg-blue-500 flex items-center justify-center">
                                <span class="text-lg font-medium text-white">
                                    {{ strtoupper(substr($customer['name'] ?? 'U', 0, 2)) }}
                                </span>
                            </div>
                        </div>
                        <div class="ml-4">
                            <h1 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                                {{ $customer['name'] ?? 'Unknown Customer' }}
                                <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                    {{ ($customer['status'] ?? 'active') === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                    {{ ucfirst($customer['status'] ?? 'active') }}
                                </span>
                            </h1>
                            <p class="mt-1 text-sm text-gray-500">
                                <i class="fas fa-envelope mr-1"></i>{{ $customer['email'] ?? 'N/A' }}
                                @if($customer['company'] ?? false)
                                    <span class="ml-4">
                                        <i class="fas fa-building mr-1"></i>{{ $customer['company'] }}
                                    </span>
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
                <div class="mt-4 flex md:mt-0 md:ml-4 space-x-3">
                    <button onclick="toggleStatus({{ $customer['id'] }}, '{{ $customer['status'] ?? 'active' }}')"
                            class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                        <i class="fas fa-toggle-{{ ($customer['status'] ?? 'active') === 'active' ? 'on text-green-500' : 'off text-gray-400' }} mr-2"></i>
                        {{ ($customer['status'] ?? 'active') === 'active' ? 'Deactivate' : 'Activate' }}
                    </button>
                    <a href="{{ route('customers.edit', $customer['id']) }}" 
                       class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                        <i class="fas fa-edit mr-2"></i>Edit Customer
                    </a>
                </div>
            </div>

            <!-- Customer Details Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                <!-- Contact Information -->
                <div class="lg:col-span-2">
                    <div class="bg-white shadow rounded-lg">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-medium text-gray-900">
                                <i class="fas fa-address-card mr-2 text-blue-500"></i>Contact Information
                            </h3>
                        </div>
                        <div class="px-6 py-4">
                            <dl class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-6">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Full Name</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ $customer['name'] ?? 'N/A' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Email Address</dt>
                                    <dd class="mt-1 text-sm text-gray-900">
                                        @if($customer['email'] ?? false)
                                            <a href="mailto:{{ $customer['email'] }}" class="text-blue-600 hover:text-blue-500">
                                                {{ $customer['email'] }}
                                            </a>
                                        @else
                                            N/A
                                        @endif
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Phone Number</dt>
                                    <dd class="mt-1 text-sm text-gray-900">
                                        @if($customer['phone'] ?? false)
                                            <a href="tel:{{ $customer['phone'] }}" class="text-blue-600 hover:text-blue-500">
                                                {{ $customer['phone'] }}
                                            </a>
                                        @else
                                            N/A
                                        @endif
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Company</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ $customer['company'] ?? 'N/A' }}</dd>
                                </div>
                                <div class="md:col-span-2">
                                    <dt class="text-sm font-medium text-gray-500">Address</dt>
                                    <dd class="mt-1 text-sm text-gray-900">
                                        @if($customer['address'] ?? false)
                                            {{ $customer['address'] }}
                                        @else
                                            N/A
                                        @endif
                                    </dd>
                                </div>
                                @if($customer['notes'] ?? false)
                                    <div class="md:col-span-2">
                                        <dt class="text-sm font-medium text-gray-500">Internal Notes</dt>
                                        <dd class="mt-1 text-sm text-gray-900 bg-gray-50 p-3 rounded">
                                            {{ $customer['notes'] }}
                                        </dd>
                                    </div>
                                @endif
                            </dl>
                        </div>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="space-y-6">
                    <!-- Account Info -->
                    <div class="bg-white shadow rounded-lg">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-medium text-gray-900">
                                <i class="fas fa-info-circle mr-2 text-blue-500"></i>Account Details
                            </h3>
                        </div>
                        <div class="px-6 py-4">
                            <dl class="space-y-4">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Customer ID</dt>
                                    <dd class="mt-1 text-sm text-gray-900 font-mono">{{ $customer['id'] ?? 'N/A' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Status</dt>
                                    <dd class="mt-1">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                            {{ ($customer['status'] ?? 'active') === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                            <i class="fas fa-circle mr-1 text-xs"></i>
                                            {{ ucfirst($customer['status'] ?? 'active') }}
                                        </span>
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Created</dt>
                                    <dd class="mt-1 text-sm text-gray-900">
                                        {{ isset($customer['created_at']) ? date('M j, Y g:i A', strtotime($customer['created_at'])) : 'N/A' }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Last Updated</dt>
                                    <dd class="mt-1 text-sm text-gray-900">
                                        {{ isset($customer['updated_at']) ? date('M j, Y g:i A', strtotime($customer['updated_at'])) : 'N/A' }}
                                    </dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="bg-white shadow rounded-lg">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-medium text-gray-900">
                                <i class="fas fa-bolt mr-2 text-blue-500"></i>Quick Actions
                            </h3>
                        </div>
                        <div class="px-6 py-4 space-y-3">
                            <button class="w-full inline-flex items-center justify-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                <i class="fas fa-file-alt mr-2"></i>Create Proposal
                            </button>
                            <button class="w-full inline-flex items-center justify-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                <i class="fas fa-file-invoice mr-2"></i>Create Invoice
                            </button>
                            <button class="w-full inline-flex items-center justify-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                <i class="fas fa-envelope mr-2"></i>Send Email
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Related Data Tabs -->
            <div class="bg-white shadow rounded-lg" x-data="{ activeTab: 'proposals' }">
                <div class="border-b border-gray-200">
                    <nav class="-mb-px flex space-x-8 px-6">
                        <button @click="activeTab = 'proposals'" 
                                :class="activeTab === 'proposals' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                            <i class="fas fa-file-alt mr-2"></i>Proposals ({{ count($proposals) }})
                        </button>
                        <button @click="activeTab = 'invoices'" 
                                :class="activeTab === 'invoices' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                            <i class="fas fa-file-invoice mr-2"></i>Invoices ({{ count($invoices) }})
                        </button>
                        <button @click="activeTab = 'activity'" 
                                :class="activeTab === 'activity' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                            <i class="fas fa-history mr-2"></i>Activity
                        </button>
                    </nav>
                </div>

                <!-- Proposals Tab -->
                <div x-show="activeTab === 'proposals'" class="p-6">
                    @if(count($proposals) > 0)
                        <div class="space-y-4">
                            @foreach($proposals as $proposal)
                                <div class="border border-gray-200 rounded-lg p-4">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <h4 class="text-sm font-medium text-gray-900">{{ $proposal['title'] ?? 'Untitled Proposal' }}</h4>
                                            <p class="text-sm text-gray-500">{{ $proposal['description'] ?? '' }}</p>
                                        </div>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            {{ ucfirst($proposal['status'] ?? 'draft') }}
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-8">
                            <i class="fas fa-file-alt text-4xl text-gray-400 mb-4"></i>
                            <h3 class="text-sm font-medium text-gray-900">No proposals yet</h3>
                            <p class="mt-1 text-sm text-gray-500">Create a proposal for this customer to get started.</p>
                            <div class="mt-4">
                                <button class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                                    <i class="fas fa-plus mr-2"></i>Create Proposal
                                </button>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Invoices Tab -->
                <div x-show="activeTab === 'invoices'" class="p-6">
                    @if(count($invoices) > 0)
                        <div class="space-y-4">
                            @foreach($invoices as $invoice)
                                <div class="border border-gray-200 rounded-lg p-4">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <h4 class="text-sm font-medium text-gray-900">Invoice #{{ $invoice['number'] ?? 'N/A' }}</h4>
                                            <p class="text-sm text-gray-500">${{ number_format($invoice['total'] ?? 0, 2) }}</p>
                                        </div>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            {{ ucfirst($invoice['status'] ?? 'draft') }}
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-8">
                            <i class="fas fa-file-invoice text-4xl text-gray-400 mb-4"></i>
                            <h3 class="text-sm font-medium text-gray-900">No invoices yet</h3>
                            <p class="mt-1 text-sm text-gray-500">Create an invoice for this customer to get started.</p>
                            <div class="mt-4">
                                <button class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                                    <i class="fas fa-plus mr-2"></i>Create Invoice
                                </button>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Activity Tab -->
                <div x-show="activeTab === 'activity'" class="p-6">
                    <div class="text-center py-8">
                        <i class="fas fa-history text-4xl text-gray-400 mb-4"></i>
                        <h3 class="text-sm font-medium text-gray-900">Activity tracking coming soon</h3>
                        <p class="mt-1 text-sm text-gray-500">Customer activity and interaction history will be displayed here.</p>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        async function toggleStatus(customerId, currentStatus) {
            try {
                const response = await fetch(`/customers/${customerId}/toggle-status`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                    }
                });

                const data = await response.json();
                
                if (data.success) {
                    // Reload the page to show updated status
                    location.reload();
                } else {
                    alert('Failed to update status: ' + (data.error || 'Unknown error'));
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Failed to update status: ' + error.message);
            }
        }
    </script>
</body>
</html> 
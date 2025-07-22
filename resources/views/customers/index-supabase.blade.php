<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customers - Connectly CRM</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="min-h-screen">
        <!-- Header -->
        <header class="bg-white shadow">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center space-x-4">
                        <a href="{{ route('dashboard') }}" class="text-blue-600 hover:text-blue-500">
                            <i class="fas fa-arrow-left mr-2"></i>Back to Dashboard
                        </a>
                        <h1 class="text-xl font-semibold text-gray-900">Customer Management</h1>
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
            <!-- Success/Error Messages -->
            @if(session('success'))
                <div class="bg-green-50 border-l-4 border-green-400 p-4 mb-6">
                    <div class="flex">
                        <i class="fas fa-check-circle text-green-400 mr-3"></i>
                        <p class="text-green-700">{{ session('success') }}</p>
                    </div>
                </div>
            @endif

            @if(session('error'))
                <div class="bg-red-50 border-l-4 border-red-400 p-4 mb-6">
                    <div class="flex">
                        <i class="fas fa-exclamation-triangle text-red-400 mr-3"></i>
                        <p class="text-red-700">{{ session('error') }}</p>
                    </div>
                </div>
            @endif

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-users text-2xl text-blue-500"></i>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Total Customers</dt>
                                    <dd class="text-lg font-medium text-gray-900">{{ $stats['total'] ?? 0 }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-check-circle text-2xl text-green-500"></i>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Active Customers</dt>
                                    <dd class="text-lg font-medium text-gray-900">{{ $stats['active'] ?? 0 }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-pause-circle text-2xl text-yellow-500"></i>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Inactive Customers</dt>
                                    <dd class="text-lg font-medium text-gray-900">{{ $stats['inactive'] ?? 0 }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search and Actions -->
            <div class="bg-white shadow rounded-lg mb-6">
                <div class="px-4 py-5 sm:p-6">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex-1">
                            <form method="GET" action="{{ route('customers.index') }}" class="flex space-x-4">
                                <div class="flex-1">
                                    <input type="text" 
                                           name="search" 
                                           value="{{ $search ?? '' }}"
                                           placeholder="Search customers by name, email, or company..." 
                                           class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                </div>
                                <div>
                                    <select name="status" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                        <option value="all" {{ ($status ?? '') === 'all' ? 'selected' : '' }}>All Status</option>
                                        <option value="active" {{ ($status ?? '') === 'active' ? 'selected' : '' }}>Active</option>
                                        <option value="inactive" {{ ($status ?? '') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                                    </select>
                                </div>
                                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                                    <i class="fas fa-search mr-1"></i>Search
                                </button>
                            </form>
                        </div>
                        <div class="mt-4 sm:mt-0 sm:ml-4 flex space-x-2">
                            <a href="{{ route('customers.create') }}" 
                               class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md">
                                <i class="fas fa-plus mr-1"></i>Add Customer
                            </a>
                            <a href="{{ route('customers.export', request()->query()) }}" 
                               class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md">
                                <i class="fas fa-download mr-1"></i>Export CSV
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Customers Table -->
            <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                <div class="px-4 py-5 sm:px-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">
                        Customer List
                        @if(isset($paginatedCustomers))
                            ({{ count($paginatedCustomers) }} customers)
                        @endif
                    </h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Customer
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Contact
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Company
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Status
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Created
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($paginatedCustomers ?? [] as $customer)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10">
                                                <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                                                    <span class="text-sm font-medium text-blue-600">
                                                        {{ strtoupper(substr($customer['name'] ?? '', 0, 2)) }}
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900">{{ $customer['name'] ?? 'N/A' }}</div>
                                                <div class="text-sm text-gray-500">ID: {{ $customer['id'] ?? 'N/A' }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">{{ $customer['email'] ?? 'N/A' }}</div>
                                        <div class="text-sm text-gray-500">{{ $customer['phone'] ?? 'No phone' }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $customer['company'] ?? 'No company' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if(($customer['status'] ?? '') === 'active')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                <i class="fas fa-circle text-green-400 mr-1" style="font-size: 8px;"></i>
                                                Active
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                <i class="fas fa-circle text-yellow-400 mr-1" style="font-size: 8px;"></i>
                                                Inactive
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ isset($customer['created_at']) ? \Carbon\Carbon::parse($customer['created_at'])->format('M j, Y') : 'N/A' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex items-center justify-end space-x-2">
                                            <a href="{{ route('customers.show', $customer['id']) }}" 
                                               class="text-blue-600 hover:text-blue-900">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('customers.edit', $customer['id']) }}" 
                                               class="text-green-600 hover:text-green-900">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form method="POST" action="{{ route('customers.toggle-status', $customer['id']) }}" class="inline">
                                                @csrf
                                                <button type="submit" 
                                                        class="text-yellow-600 hover:text-yellow-900"
                                                        title="Toggle Status">
                                                    <i class="fas fa-toggle-{{ ($customer['status'] ?? '') === 'active' ? 'on' : 'off' }}"></i>
                                                </button>
                                            </form>
                                            <form method="POST" action="{{ route('customers.destroy', $customer['id']) }}" 
                                                  class="inline"
                                                  onsubmit="return confirm('Are you sure you want to delete this customer?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" 
                                                        class="text-red-600 hover:text-red-900"
                                                        title="Delete Customer">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                        <div class="flex flex-col items-center">
                                            <i class="fas fa-users text-4xl text-gray-300 mb-4"></i>
                                            <p class="text-lg font-medium">No customers found</p>
                                            <p class="text-sm">Get started by adding your first customer.</p>
                                            <a href="{{ route('customers.create') }}" 
                                               class="mt-4 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                                                <i class="fas fa-plus mr-1"></i>Add Customer
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Note about Supabase -->
            <div class="mt-6 bg-blue-50 border border-blue-200 rounded-md p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-info-circle text-blue-400"></i>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-blue-800">
                            Supabase-Powered Customer Management
                        </h3>
                        <div class="mt-2 text-sm text-blue-700">
                            <p>This customer management system is powered by Supabase and includes:</p>
                            <ul class="list-disc list-inside mt-1 space-y-1">
                                <li>Full CRUD operations (Create, Read, Update, Delete)</li>
                                <li>Search and filtering capabilities</li>
                                <li>Status management and bulk actions</li>
                                <li>CSV export functionality</li>
                                <li>REST API support for all operations</li>
                                <li>Email notifications for new customers</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html> 
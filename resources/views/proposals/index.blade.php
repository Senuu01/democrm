<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proposals - Connectly CRM</title>
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
                        <a href="{{ route('dashboard') }}" class="text-xl font-semibold text-gray-900 hover:text-blue-600">
                            <i class="fas fa-arrow-left mr-2"></i>Connectly CRM
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
                    <h1 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                        <i class="fas fa-file-alt text-blue-500 mr-3"></i>Proposal Management
                    </h1>
                    <p class="mt-1 text-sm text-gray-500">Create, manage, and track your business proposals</p>
                </div>
                <div class="mt-4 flex md:mt-0 md:ml-4 space-x-3">
                    <a href="{{ route('proposals.create') }}" 
                       class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                        <i class="fas fa-plus mr-2"></i>New Proposal
                    </a>
                </div>
            </div>

            <!-- Alerts -->
            @if(session('success'))
                <div class="bg-green-50 border-l-4 border-green-400 p-4 mb-6" x-data="{ show: true }" x-show="show">
                    <div class="flex justify-between">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-check-circle text-green-400"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-green-700">{{ session('success') }}</p>
                            </div>
                        </div>
                        <button @click="show = false" class="text-green-400 hover:text-green-600">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            @endif

            @if(session('error') || isset($error))
                <div class="bg-red-50 border-l-4 border-red-400 p-4 mb-6" x-data="{ show: true }" x-show="show">
                    <div class="flex justify-between">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-exclamation-circle text-red-400"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-red-700">{{ session('error') ?? $error }}</p>
                            </div>
                        </div>
                        <button @click="show = false" class="text-red-400 hover:text-red-600">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            @endif

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-file-alt text-2xl text-blue-500"></i>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Total</dt>
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
                                <i class="fas fa-edit text-2xl text-gray-500"></i>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Draft</dt>
                                    <dd class="text-lg font-medium text-gray-900">{{ $stats['draft'] ?? 0 }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-paper-plane text-2xl text-blue-500"></i>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Sent</dt>
                                    <dd class="text-lg font-medium text-gray-900">{{ $stats['sent'] ?? 0 }}</dd>
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
                                    <dt class="text-sm font-medium text-gray-500 truncate">Accepted</dt>
                                    <dd class="text-lg font-medium text-gray-900">{{ $stats['accepted'] ?? 0 }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-dollar-sign text-2xl text-green-600"></i>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Total Value</dt>
                                    <dd class="text-lg font-medium text-gray-900">${{ number_format($stats['total_value'] ?? 0, 2) }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search and Filter -->
            <div class="bg-white shadow rounded-lg mb-6">
                <div class="p-6">
                    <form method="GET" class="space-y-4 md:space-y-0 md:flex md:items-end md:space-x-4">
                        <div class="flex-1">
                            <label for="search" class="block text-sm font-medium text-gray-700">Search</label>
                            <input type="text" name="search" id="search" value="{{ $search }}"
                                   placeholder="Search by title, description, or customer..."
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div class="md:w-48">
                            <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                            <select name="status" id="status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                <option value="">All Statuses</option>
                                <option value="draft" {{ $status === 'draft' ? 'selected' : '' }}>Draft</option>
                                <option value="sent" {{ $status === 'sent' ? 'selected' : '' }}>Sent</option>
                                <option value="accepted" {{ $status === 'accepted' ? 'selected' : '' }}>Accepted</option>
                                <option value="rejected" {{ $status === 'rejected' ? 'selected' : '' }}>Rejected</option>
                            </select>
                        </div>
                        <div class="md:w-48">
                            <label for="customer_id" class="block text-sm font-medium text-gray-700">Customer</label>
                            <select name="customer_id" id="customer_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                <option value="">All Customers</option>
                                @foreach($customers as $customer)
                                    <option value="{{ $customer['id'] }}" {{ $customer_id == $customer['id'] ? 'selected' : '' }}>
                                        {{ $customer['name'] }} {{ $customer['company'] ? '(' . $customer['company'] . ')' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex space-x-2">
                            <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                                <i class="fas fa-search mr-2"></i>Search
                            </button>
                            @if($search || $status || $customer_id)
                                <a href="{{ route('proposals.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                    <i class="fas fa-times mr-2"></i>Clear
                                </a>
                            @endif
                        </div>
                    </form>
                </div>
            </div>

            <!-- Proposal List -->
            <div class="bg-white shadow overflow-hidden sm:rounded-md">
                @if(count($proposals) > 0)
                    <ul class="divide-y divide-gray-200">
                        @foreach($proposals as $proposal)
                            <li>
                                <div class="px-4 py-4 hover:bg-gray-50">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0">
                                                <div class="h-10 w-10 rounded-full bg-blue-500 flex items-center justify-center">
                                                    <i class="fas fa-file-alt text-white"></i>
                                                </div>
                                            </div>
                                            <div class="ml-4">
                                                <div class="flex items-center">
                                                    <p class="text-sm font-medium text-gray-900">{{ $proposal['title'] ?? 'Untitled' }}</p>
                                                    <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                                        @if(($proposal['status'] ?? 'draft') === 'draft') bg-gray-100 text-gray-800
                                                        @elseif(($proposal['status'] ?? 'draft') === 'sent') bg-blue-100 text-blue-800
                                                        @elseif(($proposal['status'] ?? 'draft') === 'accepted') bg-green-100 text-green-800
                                                        @elseif(($proposal['status'] ?? 'draft') === 'rejected') bg-red-100 text-red-800
                                                        @else bg-gray-100 text-gray-800
                                                        @endif">
                                                        {{ ucfirst($proposal['status'] ?? 'draft') }}
                                                    </span>
                                                </div>
                                                <div class="mt-1 flex items-center text-sm text-gray-500">
                                                    <i class="fas fa-user mr-1"></i>
                                                    <span>{{ $proposal['customer']['name'] ?? 'Unknown Customer' }}</span>
                                                    @if($proposal['customer']['company'] ?? false)
                                                        <span class="ml-2">
                                                            <i class="fas fa-building mr-1"></i>{{ $proposal['customer']['company'] }}
                                                        </span>
                                                    @endif
                                                    <span class="ml-4">
                                                        <i class="fas fa-dollar-sign mr-1"></i>
                                                        ${{ number_format($proposal['total_amount'] ?? 0, 2) }}
                                                    </span>
                                                    <span class="ml-4">
                                                        <i class="fas fa-calendar mr-1"></i>
                                                        Valid until {{ date('M j, Y', strtotime($proposal['valid_until'] ?? 'now')) }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="flex items-center space-x-2">
                                            <!-- Status Dropdown -->
                                            <div x-data="{ open: false }" class="relative">
                                                <button @click="open = !open" class="p-2 text-gray-400 hover:text-gray-600" title="Change Status">
                                                    <i class="fas fa-cog"></i>
                                                </button>
                                                <div x-show="open" @click.away="open = false" 
                                                     class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-10">
                                                    <div class="py-1">
                                                        <button onclick="updateStatus({{ $proposal['id'] }}, 'draft')" 
                                                                class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                            <i class="fas fa-edit mr-2"></i>Mark as Draft
                                                        </button>
                                                        <button onclick="updateStatus({{ $proposal['id'] }}, 'sent')" 
                                                                class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                            <i class="fas fa-paper-plane mr-2"></i>Mark as Sent
                                                        </button>
                                                        <button onclick="updateStatus({{ $proposal['id'] }}, 'accepted')" 
                                                                class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                            <i class="fas fa-check-circle mr-2"></i>Mark as Accepted
                                                        </button>
                                                        <button onclick="updateStatus({{ $proposal['id'] }}, 'rejected')" 
                                                                class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                            <i class="fas fa-times-circle mr-2"></i>Mark as Rejected
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- PDF Download -->
                                            <a href="{{ route('proposals.pdf', $proposal['id']) }}" 
                                               class="p-2 text-red-600 hover:text-red-900" title="Download PDF">
                                                <i class="fas fa-file-pdf"></i>
                                            </a>
                                            
                                            <!-- View -->
                                            <a href="{{ route('proposals.show', $proposal['id']) }}" 
                                               class="p-2 text-blue-600 hover:text-blue-900" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            
                                            <!-- Edit -->
                                            <a href="{{ route('proposals.edit', $proposal['id']) }}" 
                                               class="p-2 text-green-600 hover:text-green-900" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            
                                            <!-- Delete -->
                                            <form method="POST" action="{{ route('proposals.destroy', $proposal['id']) }}" 
                                                  onsubmit="return confirm('Are you sure you want to delete this proposal?')" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="p-2 text-red-600 hover:text-red-900" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ul>

                    <!-- Pagination -->
                    @if($totalPages > 1)
                        <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
                            <div class="flex-1 flex justify-between sm:hidden">
                                @if($currentPage > 1)
                                    <a href="?page={{ $currentPage - 1 }}{{ $search ? '&search=' . $search : '' }}{{ $status ? '&status=' . $status : '' }}{{ $customer_id ? '&customer_id=' . $customer_id : '' }}" 
                                       class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                        Previous
                                    </a>
                                @endif
                                @if($currentPage < $totalPages)
                                    <a href="?page={{ $currentPage + 1 }}{{ $search ? '&search=' . $search : '' }}{{ $status ? '&status=' . $status : '' }}{{ $customer_id ? '&customer_id=' . $customer_id : '' }}" 
                                       class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                        Next
                                    </a>
                                @endif
                            </div>
                            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                                <div>
                                    <p class="text-sm text-gray-700">
                                        Page <span class="font-medium">{{ $currentPage }}</span> of <span class="font-medium">{{ $totalPages }}</span>
                                    </p>
                                </div>
                                <div>
                                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                                        @for($i = 1; $i <= min($totalPages, 5); $i++)
                                            <a href="?page={{ $i }}{{ $search ? '&search=' . $search : '' }}{{ $status ? '&status=' . $status : '' }}{{ $customer_id ? '&customer_id=' . $customer_id : '' }}" 
                                               class="relative inline-flex items-center px-4 py-2 border text-sm font-medium
                                                      {{ $i === $currentPage ? 'bg-blue-50 border-blue-500 text-blue-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50' }}">
                                                {{ $i }}
                                            </a>
                                        @endfor
                                    </nav>
                                </div>
                            </div>
                        </div>
                    @endif
                @else
                    <div class="text-center py-12">
                        <i class="fas fa-file-alt text-4xl text-gray-400 mb-4"></i>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No proposals found</h3>
                        <p class="mt-1 text-sm text-gray-500">Get started by creating your first proposal.</p>
                        <div class="mt-6">
                            <a href="{{ route('proposals.create') }}" 
                               class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                                <i class="fas fa-plus mr-2"></i>New Proposal
                            </a>
                        </div>
                    </div>
                @endif
            </div>
        </main>
    </div>

    <script>
        async function updateStatus(proposalId, status) {
            try {
                const response = await fetch(`/proposals/${proposalId}/status`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                    },
                    body: JSON.stringify({ status: status })
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
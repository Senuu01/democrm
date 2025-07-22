<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard - Connectly CRM</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <h1 class="text-2xl font-bold text-indigo-600">Connectly CRM</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <nav class="hidden md:flex space-x-8">
                        <a href="{{ route('dashboard') }}" class="text-indigo-600 border-b-2 border-indigo-600 pb-1 px-1 text-sm font-medium">Dashboard</a>
                        <a href="{{ route('customers.index') }}" class="text-gray-500 hover:text-gray-700 px-1 pb-1 text-sm font-medium">Customers</a>
                        <a href="{{ route('proposals.index') }}" class="text-gray-500 hover:text-gray-700 px-1 pb-1 text-sm font-medium">Proposals</a>
                        <a href="{{ route('invoices.index') }}" class="text-gray-500 hover:text-gray-700 px-1 pb-1 text-sm font-medium">Invoices</a>
                        <a href="{{ route('transactions.index') }}" class="text-gray-500 hover:text-gray-700 px-1 pb-1 text-sm font-medium">Transactions</a>
                    </nav>
                    <div class="text-right">
                        <div class="text-gray-900 font-medium">User</div>
                        <div class="text-gray-600 text-sm">{{ session('user_email', 'user@example.com') }}</div>
                    </div>
                    <form method="POST" action="{{ route('auth.logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600 text-sm">
                            Logout
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Welcome Section -->
        <div class="bg-white overflow-hidden shadow rounded-lg mb-6">
            <div class="px-6 py-4">
                <h2 class="text-2xl font-bold text-gray-900 mb-2">Welcome to your CRM Dashboard!</h2>
                <p class="text-gray-600">Manage customers, create proposals, send invoices, and track your business growth.</p>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="bg-white overflow-hidden shadow rounded-lg mb-6">
            <div class="px-6 py-4">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Quick Actions</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <a href="{{ route('customers.create') }}" class="inline-flex items-center justify-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 transition">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M12 4v16m8-8H4"></path></svg>
                        Add Customer
                    </a>
                    <a href="{{ route('proposals.create') }}" class="inline-flex items-center justify-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 transition">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M12 4v16m8-8H4"></path></svg>
                        Create Proposal
                    </a>
                    <a href="{{ route('invoices.create') }}" class="inline-flex items-center justify-center px-4 py-2 bg-yellow-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-yellow-700 transition">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M12 4v16m8-8H4"></path></svg>
                        Create Invoice
                    </a>
                    <a href="{{ route('customers-export') }}" class="inline-flex items-center justify-center px-4 py-2 bg-purple-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-purple-700 transition">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7,10 12,15 17,10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                        Export Data
                    </a>
                </div>
            </div>
        </div>

        <!-- Stats Overview -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
            <!-- Total Customers -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Total Customers</dt>
                                <dd class="text-lg font-medium text-gray-900">{{ $totalCustomers ?? 0 }}</dd>
                            </dl>
                        </div>
                    </div>
                    <div class="mt-2">
                        <div class="flex items-center text-sm text-green-600">
                            <span>{{ $activeCustomers ?? 0 }} active</span>
                            <span class="mx-2">•</span>
                            <span>{{ $inactiveCustomers ?? 0 }} inactive</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Total Proposals -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Total Proposals</dt>
                                <dd class="text-lg font-medium text-gray-900">{{ $totalProposals ?? 0 }}</dd>
                            </dl>
                        </div>
                    </div>
                    <div class="mt-2">
                        <div class="flex items-center text-sm text-green-600">
                            <span>{{ $acceptedProposals ?? 0 }} accepted</span>
                            <span class="mx-2">•</span>
                            <span>{{ $pendingProposals ?? 0 }} pending</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Total Invoices -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-yellow-500 rounded-full flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Total Invoices</dt>
                                <dd class="text-lg font-medium text-gray-900">{{ $totalInvoices ?? 0 }}</dd>
                            </dl>
                        </div>
                    </div>
                    <div class="mt-2">
                        <div class="flex items-center text-sm text-green-600">
                            <span>{{ $paidInvoices ?? 0 }} paid</span>
                            <span class="mx-2">•</span>
                            <span>{{ $pendingInvoices ?? 0 }} pending</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Total Revenue -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-indigo-500 rounded-full flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Total Revenue</dt>
                                <dd class="text-lg font-medium text-gray-900">${{ number_format($totalRevenue ?? 0, 2) }}</dd>
                            </dl>
                        </div>
                    </div>
                    <div class="mt-2">
                        <div class="flex items-center text-sm text-green-600">
                            <span>${{ number_format($monthlyRevenue ?? 0, 2) }} this month</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts and Analytics -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <!-- Revenue Trend -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Revenue Trend (Last 12 Months)</h3>
                </div>
                <div class="p-6">
                    <canvas id="revenueChart" width="400" height="200"></canvas>
                </div>
            </div>

            <!-- Customer Growth -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Customer Growth</h3>
                </div>
                <div class="p-6">
                    <canvas id="customerChart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>

        <!-- Recent Activities -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Recent Customers -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Recent Customers</h3>
                </div>
                <div class="p-6">
                    @if(isset($recentCustomers) && count($recentCustomers) > 0)
                        <ul class="space-y-3">
                            @foreach($recentCustomers as $customer)
                            <li class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-900">{{ $customer->name }}</p>
                                    <p class="text-sm text-gray-500">{{ $customer->email }}</p>
                                </div>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $customer->status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                    {{ $customer->status }}
                                </span>
                            </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-gray-500 text-center py-8">No customers yet. <a href="{{ route('customers.create') }}" class="text-indigo-600 hover:text-indigo-500">Create your first customer</a></p>
                    @endif
                </div>
            </div>

            <!-- Recent Transactions -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Recent Transactions</h3>
                </div>
                <div class="p-6">
                    @if(isset($recentTransactions) && count($recentTransactions) > 0)
                        <ul class="space-y-3">
                            @foreach($recentTransactions as $transaction)
                            <li class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-900">${{ number_format($transaction->amount, 2) }}</p>
                                    <p class="text-sm text-gray-500">{{ $transaction->customer->name ?? 'N/A' }}</p>
                                </div>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                    {{ $transaction->status === 'completed' ? 'bg-green-100 text-green-800' : 
                                       ($transaction->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                    {{ $transaction->status }}
                                </span>
                            </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-gray-500 text-center py-8">No transactions yet.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <script>
        // Revenue Chart
        @if(isset($revenueByMonth))
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        const revenueChart = new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: {!! json_encode(array_column($revenueByMonth, 'month')) !!},
                datasets: [{
                    label: 'Revenue',
                    data: {!! json_encode(array_column($revenueByMonth, 'revenue')) !!},
                    borderColor: 'rgb(99, 102, 241)',
                    backgroundColor: 'rgba(99, 102, 241, 0.1)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '$' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
        @endif

        // Customer Growth Chart
        @if(isset($customerGrowth))
        const customerCtx = document.getElementById('customerChart').getContext('2d');
        const customerChart = new Chart(customerCtx, {
            type: 'bar',
            data: {
                labels: {!! json_encode(array_column($customerGrowth, 'month')) !!},
                datasets: [{
                    label: 'Customers',
                    data: {!! json_encode(array_column($customerGrowth, 'customers')) !!},
                    backgroundColor: 'rgba(34, 197, 94, 0.8)',
                    borderColor: 'rgb(34, 197, 94)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
        @endif
    </script>
</body>
</html>

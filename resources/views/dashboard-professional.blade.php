<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard - Connectly CRM</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <h1 class="text-2xl font-bold text-indigo-600">
                        <i class="fas fa-chart-line mr-2"></i>Connectly CRM
                    </h1>
                </div>
                <div class="flex items-center space-x-4">
                    <nav class="hidden md:flex space-x-8">
                        <a href="{{ route('dashboard') }}" class="text-indigo-600 border-b-2 border-indigo-600 pb-1 px-1 text-sm font-medium">
                            <i class="fas fa-tachometer-alt mr-1"></i>Dashboard
                        </a>
                        <a href="{{ route('customers.index') }}" class="text-gray-500 hover:text-gray-700 px-1 pb-1 text-sm font-medium">
                            <i class="fas fa-users mr-1"></i>Customers
                        </a>
                        <a href="{{ route('proposals.index') }}" class="text-gray-500 hover:text-gray-700 px-1 pb-1 text-sm font-medium">
                            <i class="fas fa-file-alt mr-1"></i>Proposals
                        </a>
                        <a href="{{ route('invoices.index') }}" class="text-gray-500 hover:text-gray-700 px-1 pb-1 text-sm font-medium">
                            <i class="fas fa-file-invoice-dollar mr-1"></i>Invoices
                        </a>
                        <a href="{{ route('settings.index') }}" class="text-gray-500 hover:text-gray-700 px-1 pb-1 text-sm font-medium">
                            <i class="fas fa-cog mr-1"></i>Settings
                        </a>
                    </nav>
                    <div class="text-right">
                        <div class="text-gray-900 font-medium">{{ $user_name }}</div>
                        <div class="text-gray-600 text-sm">{{ $user_email }}</div>
                    </div>
                    <form method="POST" action="{{ route('auth.logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600 text-sm">
                            <i class="fas fa-sign-out-alt mr-1"></i>Logout
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Welcome Section -->
        <div class="bg-gradient-to-r from-indigo-500 to-purple-600 overflow-hidden shadow rounded-lg mb-6">
            <div class="px-6 py-8 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-3xl font-bold mb-2">Welcome back, {{ $user_name }}!</h2>
                        <p class="text-indigo-100 text-lg">Here's what's happening with your business today.</p>
                    </div>
                    <div class="hidden md:block">
                        <i class="fas fa-chart-line text-6xl text-white opacity-20"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="bg-white overflow-hidden shadow rounded-lg mb-6">
            <div class="px-6 py-4">
                <h3 class="text-lg font-medium text-gray-900 mb-4">
                    <i class="fas fa-bolt text-yellow-500 mr-2"></i>Quick Actions
                </h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <a href="{{ route('customers.create') }}" class="inline-flex items-center justify-center px-4 py-3 bg-blue-600 border border-transparent rounded-lg font-semibold text-sm text-white uppercase tracking-wider hover:bg-blue-700 transition duration-150 ease-in-out transform hover:scale-105">
                        <i class="fas fa-user-plus mr-2"></i>Add Customer
                    </a>
                    <a href="{{ route('proposals.create') }}" class="inline-flex items-center justify-center px-4 py-3 bg-green-600 border border-transparent rounded-lg font-semibold text-sm text-white uppercase tracking-wider hover:bg-green-700 transition duration-150 ease-in-out transform hover:scale-105">
                        <i class="fas fa-file-plus mr-2"></i>New Proposal
                    </a>
                    <a href="{{ route('invoices.create') }}" class="inline-flex items-center justify-center px-4 py-3 bg-yellow-600 border border-transparent rounded-lg font-semibold text-sm text-white uppercase tracking-wider hover:bg-yellow-700 transition duration-150 ease-in-out transform hover:scale-105">
                        <i class="fas fa-receipt mr-2"></i>Create Invoice
                    </a>
                    <a href="{{ route('api.docs') }}" class="inline-flex items-center justify-center px-4 py-3 bg-purple-600 border border-transparent rounded-lg font-semibold text-sm text-white uppercase tracking-wider hover:bg-purple-700 transition duration-150 ease-in-out transform hover:scale-105">
                        <i class="fas fa-code mr-2"></i>API Docs
                    </a>
                </div>
            </div>
        </div>

        <!-- Key Performance Indicators -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
            <!-- Total Customers -->
            <div class="bg-white overflow-hidden shadow rounded-lg border-l-4 border-blue-500">
                <div class="p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-users text-2xl text-blue-600"></i>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Total Customers</dt>
                                <dd class="text-2xl font-bold text-gray-900">{{ $totalCustomers }}</dd>
                            </dl>
                        </div>
                    </div>
                    <div class="mt-4">
                        <div class="flex items-center text-sm">
                            <span class="text-green-600 font-medium">{{ $activeCustomers }} active</span>
                            <span class="mx-2 text-gray-400">•</span>
                            <span class="text-gray-600">{{ $newCustomersThisMonth }} new this month</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Total Proposals -->
            <div class="bg-white overflow-hidden shadow rounded-lg border-l-4 border-green-500">
                <div class="p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-file-alt text-2xl text-green-600"></i>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Total Proposals</dt>
                                <dd class="text-2xl font-bold text-gray-900">{{ $totalProposals }}</dd>
                            </dl>
                        </div>
                    </div>
                    <div class="mt-4">
                        <div class="flex items-center text-sm">
                            <span class="text-green-600 font-medium">{{ $proposalAcceptanceRate }}% acceptance rate</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Total Invoices -->
            <div class="bg-white overflow-hidden shadow rounded-lg border-l-4 border-yellow-500">
                <div class="p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-file-invoice-dollar text-2xl text-yellow-600"></i>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Total Invoices</dt>
                                <dd class="text-2xl font-bold text-gray-900">{{ $totalInvoices }}</dd>
                            </dl>
                        </div>
                    </div>
                    <div class="mt-4">
                        <div class="flex items-center text-sm">
                            <span class="text-green-600 font-medium">{{ $paidInvoices }} paid</span>
                            <span class="mx-2 text-gray-400">•</span>
                            <span class="text-red-600">{{ $overdueInvoices }} overdue</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Total Revenue -->
            <div class="bg-white overflow-hidden shadow rounded-lg border-l-4 border-indigo-500">
                <div class="p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-12 h-12 bg-indigo-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-dollar-sign text-2xl text-indigo-600"></i>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Total Revenue</dt>
                                <dd class="text-2xl font-bold text-gray-900">${{ number_format($totalRevenue, 2) }}</dd>
                            </dl>
                        </div>
                    </div>
                    <div class="mt-4">
                        <div class="flex items-center text-sm">
                            <span class="text-green-600 font-medium">${{ number_format($monthlyRevenue, 2) }} this month</span>
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
                    <h3 class="text-lg font-medium text-gray-900">
                        <i class="fas fa-chart-area text-indigo-500 mr-2"></i>Revenue Trend (Last 12 Months)
                    </h3>
                </div>
                <div class="p-6">
                    <canvas id="revenueChart" width="400" height="200"></canvas>
                </div>
            </div>

            <!-- Customer Growth -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">
                        <i class="fas fa-chart-line text-green-500 mr-2"></i>Customer Growth
                    </h3>
                </div>
                <div class="p-6">
                    <canvas id="customerChart" width="400" height="200"></canvas>
                </div>
            </div>
        </div>

        <!-- Recent Activities and Data -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Recent Customers -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">
                        <i class="fas fa-user-plus text-blue-500 mr-2"></i>Recent Customers
                    </h3>
                </div>
                <div class="p-6">
                    @if(count($recentCustomers) > 0)
                        <ul class="space-y-4">
                            @foreach($recentCustomers as $customer)
                            <li class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center mr-3">
                                        <span class="text-white font-bold">{{ strtoupper(substr($customer['name'] ?? 'N', 0, 1)) }}</span>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">{{ $customer['name'] ?? 'N/A' }}</p>
                                        <p class="text-sm text-gray-500">{{ $customer['email'] ?? 'N/A' }}</p>
                                    </div>
                                </div>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ ($customer['status'] ?? 'active') === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                    {{ $customer['status'] ?? 'active' }}
                                </span>
                            </li>
                            @endforeach
                        </ul>
                    @else
                        <div class="text-center py-8">
                            <i class="fas fa-users text-4xl text-gray-300 mb-4"></i>
                            <p class="text-gray-500">No customers yet.</p>
                            <a href="{{ route('customers.create') }}" class="text-indigo-600 hover:text-indigo-500 font-medium">Create your first customer</a>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Recent Transactions -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">
                        <i class="fas fa-money-bill-wave text-green-500 mr-2"></i>Recent Transactions
                    </h3>
                </div>
                <div class="p-6">
                    @if(count($recentTransactions) > 0)
                        <ul class="space-y-4">
                            @foreach($recentTransactions as $transaction)
                            <li class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 bg-green-500 rounded-full flex items-center justify-center mr-3">
                                        <i class="fas fa-dollar-sign text-white"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">${{ number_format($transaction['amount'] ?? 0, 2) }}</p>
                                        <p class="text-sm text-gray-500">{{ \Carbon\Carbon::parse($transaction['created_at'] ?? now())->format('M j, Y') }}</p>
                                    </div>
                                </div>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                    {{ ($transaction['status'] ?? 'pending') === 'completed' ? 'bg-green-100 text-green-800' : 
                                       (($transaction['status'] ?? 'pending') === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                    {{ $transaction['status'] ?? 'pending' }}
                                </span>
                            </li>
                            @endforeach
                        </ul>
                    @else
                        <div class="text-center py-8">
                            <i class="fas fa-money-bill-wave text-4xl text-gray-300 mb-4"></i>
                            <p class="text-gray-500">No transactions yet.</p>
                            <p class="text-sm text-gray-400">Transactions will appear here when invoices are paid.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Chart.js Scripts -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Revenue Chart
            const revenueCtx = document.getElementById('revenueChart').getContext('2d');
            const revenueData = @json($revenueByMonth);
            
            new Chart(revenueCtx, {
                type: 'line',
                data: {
                    labels: revenueData.map(item => item.month),
                    datasets: [{
                        label: 'Revenue',
                        data: revenueData.map(item => item.revenue),
                        borderColor: 'rgb(99, 102, 241)',
                        backgroundColor: 'rgba(99, 102, 241, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '$' + value.toLocaleString();
                                }
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });

            // Customer Growth Chart
            const customerCtx = document.getElementById('customerChart').getContext('2d');
            const customerData = @json($customerGrowth);
            
            new Chart(customerCtx, {
                type: 'line',
                data: {
                    labels: customerData.map(item => item.month),
                    datasets: [{
                        label: 'Customers',
                        data: customerData.map(item => item.customers),
                        borderColor: 'rgb(34, 197, 94)',
                        backgroundColor: 'rgba(34, 197, 94, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        });
    </script>
</body>
</html> 
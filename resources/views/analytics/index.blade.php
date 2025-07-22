<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics Dashboard - Connectly CRM</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        <main class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8" x-data="analyticsApp()">
            <!-- Page Header -->
            <div class="md:flex md:items-center md:justify-between mb-8">
                <div class="flex-1 min-w-0">
                    <h1 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                        <i class="fas fa-chart-line text-blue-500 mr-3"></i>Analytics Dashboard
                    </h1>
                    <p class="mt-1 text-sm text-gray-500">Comprehensive business insights and performance metrics</p>
                </div>
                <div class="mt-4 flex md:mt-0 md:ml-4 space-x-3">
                    <form method="GET" action="{{ route('analytics.index') }}" class="flex items-center space-x-2">
                        <label for="timeframe" class="text-sm font-medium text-gray-700">Timeframe:</label>
                        <select name="timeframe" id="timeframe" onchange="this.form.submit()" 
                                class="border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                            <option value="7" {{ $timeframe == '7' ? 'selected' : '' }}>Last 7 days</option>
                            <option value="30" {{ $timeframe == '30' ? 'selected' : '' }}>Last 30 days</option>
                            <option value="90" {{ $timeframe == '90' ? 'selected' : '' }}>Last 90 days</option>
                            <option value="365" {{ $timeframe == '365' ? 'selected' : '' }}>Last year</option>
                        </select>
                    </form>
                </div>
            </div>

            <!-- Error Alert -->
            @if(isset($error))
                <div class="bg-red-50 border-l-4 border-red-400 p-4 mb-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-circle text-red-400"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-red-700">{{ $error }}</p>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Key Metrics Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Total Revenue -->
                <div class="bg-gradient-to-r from-green-400 to-green-600 overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-dollar-sign text-2xl text-white"></i>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-green-100 truncate">Total Revenue</dt>
                                    <dd class="text-lg font-medium text-white">${{ number_format($metrics['total_revenue'], 2) }}</dd>
                                    @if($metrics['revenue_growth'] != 0)
                                        <dd class="text-sm text-green-100">
                                            <i class="fas fa-{{ $metrics['revenue_growth'] > 0 ? 'arrow-up' : 'arrow-down' }}"></i>
                                            {{ number_format(abs($metrics['revenue_growth']), 1) }}% vs previous period
                                        </dd>
                                    @endif
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Total Customers -->
                <div class="bg-gradient-to-r from-blue-400 to-blue-600 overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-users text-2xl text-white"></i>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-blue-100 truncate">Total Customers</dt>
                                    <dd class="text-lg font-medium text-white">{{ $metrics['total_customers'] }}</dd>
                                    @if($metrics['new_customers'] > 0)
                                        <dd class="text-sm text-blue-100">
                                            <i class="fas fa-plus"></i>
                                            {{ $metrics['new_customers'] }} new this period
                                        </dd>
                                    @endif
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Conversion Rate -->
                <div class="bg-gradient-to-r from-purple-400 to-purple-600 overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-chart-line text-2xl text-white"></i>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-purple-100 truncate">Conversion Rate</dt>
                                    <dd class="text-lg font-medium text-white">{{ number_format($metrics['conversion_rate'], 1) }}%</dd>
                                    <dd class="text-sm text-purple-100">Proposals to paid invoices</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Outstanding Amount -->
                <div class="bg-gradient-to-r from-orange-400 to-orange-600 overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-hourglass-half text-2xl text-white"></i>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-orange-100 truncate">Outstanding</dt>
                                    <dd class="text-lg font-medium text-white">${{ number_format($metrics['outstanding_amount'], 2) }}</dd>
                                    <dd class="text-sm text-orange-100">{{ $metrics['pending_invoices'] }} pending invoices</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Row -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <!-- Revenue Chart -->
                <div class="bg-white shadow rounded-lg p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium text-gray-900">
                            <i class="fas fa-chart-area text-green-500 mr-2"></i>Revenue Trend
                        </h3>
                        <div class="text-sm text-gray-500">Last {{ $timeframe }} days</div>
                    </div>
                    <div class="h-64">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>

                <!-- Customer Growth Chart -->
                <div class="bg-white shadow rounded-lg p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium text-gray-900">
                            <i class="fas fa-user-plus text-blue-500 mr-2"></i>Customer Growth
                        </h3>
                        <div class="text-sm text-gray-500">New customers per day</div>
                    </div>
                    <div class="h-64">
                        <canvas id="customerChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Status Distribution Charts -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <!-- Invoice Status Chart -->
                <div class="bg-white shadow rounded-lg p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium text-gray-900">
                            <i class="fas fa-file-invoice text-green-500 mr-2"></i>Invoice Status Distribution
                        </h3>
                        <div class="text-sm text-gray-500">{{ $metrics['total_invoices'] }} total invoices</div>
                    </div>
                    <div class="h-64">
                        <canvas id="invoiceStatusChart"></canvas>
                    </div>
                </div>

                <!-- Proposal Status Chart -->
                <div class="bg-white shadow rounded-lg p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium text-gray-900">
                            <i class="fas fa-file-alt text-purple-500 mr-2"></i>Proposal Status Distribution
                        </h3>
                        <div class="text-sm text-gray-500">{{ $metrics['total_proposals'] }} total proposals</div>
                    </div>
                    <div class="h-64">
                        <canvas id="proposalStatusChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Business Insights Row -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                <!-- Top Customers -->
                <div class="bg-white shadow rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">
                        <i class="fas fa-crown text-yellow-500 mr-2"></i>Top Customers
                    </h3>
                    <div class="space-y-3">
                        @forelse($topCustomers as $customerData)
                            <div class="flex items-center justify-between">
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 truncate">
                                        {{ $customerData['customer']['name'] ?? 'Unknown' }}
                                    </p>
                                    <p class="text-xs text-gray-500">
                                        {{ $customerData['invoice_count'] }} invoices
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-medium text-gray-900">
                                        ${{ number_format($customerData['revenue'], 2) }}
                                    </p>
                                    <p class="text-xs text-gray-500">
                                        ${{ number_format($customerData['average_invoice'], 2) }} avg
                                    </p>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-4">
                                <i class="fas fa-users text-gray-400 text-2xl mb-2"></i>
                                <p class="text-sm text-gray-500">No customer data yet</p>
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- Conversion Rates -->
                <div class="bg-white shadow rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">
                        <i class="fas fa-percentage text-blue-500 mr-2"></i>Conversion Rates
                    </h3>
                    <div class="space-y-4">
                        <div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Proposal Acceptance</span>
                                <span class="font-medium">{{ number_format($conversionRates['proposal_to_acceptance'] ?? 0, 1) }}%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2 mt-1">
                                <div class="bg-green-500 h-2 rounded-full" style="width: {{ min($conversionRates['proposal_to_acceptance'] ?? 0, 100) }}%"></div>
                            </div>
                        </div>
                        <div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Invoice Payment</span>
                                <span class="font-medium">{{ number_format($conversionRates['invoice_to_payment'] ?? 0, 1) }}%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2 mt-1">
                                <div class="bg-blue-500 h-2 rounded-full" style="width: {{ min($conversionRates['invoice_to_payment'] ?? 0, 100) }}%"></div>
                            </div>
                        </div>
                        <div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Overall Conversion</span>
                                <span class="font-medium">{{ number_format($conversionRates['overall_conversion'] ?? 0, 1) }}%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2 mt-1">
                                <div class="bg-purple-500 h-2 rounded-full" style="width: {{ min($conversionRates['overall_conversion'] ?? 0, 100) }}%"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Key Performance Indicators -->
                <div class="bg-white shadow rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">
                        <i class="fas fa-tachometer-alt text-red-500 mr-2"></i>Key Metrics
                    </h3>
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600">Average Invoice Value</span>
                            <span class="text-sm font-medium text-gray-900">${{ number_format($metrics['average_invoice_value'], 2) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600">Paid Invoices</span>
                            <span class="text-sm font-medium text-gray-900">{{ $metrics['paid_invoices'] }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600">Overdue Invoices</span>
                            <span class="text-sm font-medium text-red-600">{{ $metrics['overdue_invoices'] }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600">New Proposals</span>
                            <span class="text-sm font-medium text-gray-900">{{ $metrics['new_proposals'] }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600">Recent Revenue</span>
                            <span class="text-sm font-medium text-green-600">${{ number_format($metrics['recent_revenue'], 2) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activities -->
            <div class="bg-white shadow rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">
                    <i class="fas fa-history text-gray-500 mr-2"></i>Recent Activities
                </h3>
                <div class="flow-root">
                    <ul class="-mb-8">
                        @forelse($recentActivities as $index => $activity)
                            <li>
                                <div class="relative pb-8">
                                    @if($index < count($recentActivities) - 1)
                                        <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200" aria-hidden="true"></span>
                                    @endif
                                    <div class="relative flex space-x-3">
                                        <div>
                                            <span class="h-8 w-8 rounded-full flex items-center justify-center ring-8 ring-white bg-gray-100">
                                                <i class="{{ $activity['icon'] }} text-sm {{ $activity['color'] }}"></i>
                                            </span>
                                        </div>
                                        <div class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                                            <div>
                                                <p class="text-sm text-gray-900">
                                                    {{ $activity['title'] }}
                                                    <span class="font-medium text-gray-900">{{ $activity['description'] }}</span>
                                                </p>
                                            </div>
                                            <div class="text-right text-sm whitespace-nowrap text-gray-500">
                                                {{ $activity['time'] }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </li>
                        @empty
                            <li class="text-center py-8">
                                <i class="fas fa-history text-gray-400 text-2xl mb-2"></i>
                                <p class="text-sm text-gray-500">No recent activities</p>
                            </li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </main>
    </div>

    <script>
        function analyticsApp() {
            return {
                init() {
                    this.initCharts();
                },
                
                initCharts() {
                    // Chart.js configuration
                    Chart.defaults.font.family = 'Inter, system-ui, sans-serif';
                    Chart.defaults.color = '#6B7280';
                    
                    // Revenue Chart
                    const revenueCtx = document.getElementById('revenueChart').getContext('2d');
                    new Chart(revenueCtx, {
                        type: 'line',
                        data: {
                            labels: @json($chartData['dates']),
                            datasets: [{
                                label: 'Revenue',
                                data: @json($chartData['revenue']),
                                borderColor: '#10B981',
                                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                                borderWidth: 2,
                                fill: true,
                                tension: 0.4
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: false
                                }
                            },
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

                    // Customer Chart
                    const customerCtx = document.getElementById('customerChart').getContext('2d');
                    new Chart(customerCtx, {
                        type: 'bar',
                        data: {
                            labels: @json($chartData['dates']),
                            datasets: [{
                                label: 'New Customers',
                                data: @json($chartData['customers']),
                                backgroundColor: '#3B82F6',
                                borderRadius: 4
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    display: false
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        stepSize: 1
                                    }
                                }
                            }
                        }
                    });

                    // Invoice Status Chart
                    const invoiceStatusCtx = document.getElementById('invoiceStatusChart').getContext('2d');
                    new Chart(invoiceStatusCtx, {
                        type: 'doughnut',
                        data: {
                            labels: ['Draft', 'Sent', 'Paid', 'Overdue', 'Cancelled'],
                            datasets: [{
                                data: [
                                    @json($chartData['invoice_status']['draft']),
                                    @json($chartData['invoice_status']['sent']),
                                    @json($chartData['invoice_status']['paid']),
                                    @json($chartData['invoice_status']['overdue']),
                                    @json($chartData['invoice_status']['cancelled'])
                                ],
                                backgroundColor: [
                                    '#6B7280',
                                    '#3B82F6',
                                    '#10B981',
                                    '#EF4444',
                                    '#9CA3AF'
                                ]
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'bottom'
                                }
                            }
                        }
                    });

                    // Proposal Status Chart
                    const proposalStatusCtx = document.getElementById('proposalStatusChart').getContext('2d');
                    new Chart(proposalStatusCtx, {
                        type: 'doughnut',
                        data: {
                            labels: ['Draft', 'Sent', 'Accepted', 'Rejected'],
                            datasets: [{
                                data: [
                                    @json($chartData['proposal_status']['draft']),
                                    @json($chartData['proposal_status']['sent']),
                                    @json($chartData['proposal_status']['accepted']),
                                    @json($chartData['proposal_status']['rejected'])
                                ],
                                backgroundColor: [
                                    '#6B7280',
                                    '#8B5CF6',
                                    '#10B981',
                                    '#EF4444'
                                ]
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'bottom'
                                }
                            }
                        }
                    });
                }
            }
        }
    </script>
</body>
</html> 
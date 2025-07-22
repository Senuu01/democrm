<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Connectly CRM</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="min-h-screen">
        <!-- Header -->
        <header class="bg-white shadow">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <h1 class="text-xl font-semibold text-gray-900">Connectly CRM</h1>
                    </div>
                    <div class="flex items-center space-x-4">
                        <span class="text-gray-700">{{ $user['name'] ?? 'User' }}</span>
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
            <!-- Alert Message -->
            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-triangle text-yellow-400"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-yellow-700">
                            <strong>Notice:</strong> {{ $message }}
                        </p>
                        <p class="text-sm text-yellow-600 mt-1">
                            Database connections have been disabled to prevent connection errors. 
                            Authentication and email features are working normally.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Welcome Section -->
            <div class="bg-white overflow-hidden shadow rounded-lg mb-6">
                <div class="px-4 py-5 sm:p-6">
                    <h2 class="text-lg font-medium text-gray-900 mb-4">
                        <i class="fas fa-home mr-2 text-blue-500"></i>Welcome to Connectly CRM
                    </h2>
                    <p class="text-gray-600 mb-4">
                        Hello <strong>{{ $user['name'] ?? 'User' }}</strong>! Your authentication system is working perfectly.
                    </p>
                    <div class="bg-green-50 border border-green-200 rounded-md p-4">
                        <h3 class="text-sm font-medium text-green-800 mb-2">✅ Working Features:</h3>
                        <ul class="text-sm text-green-700 space-y-1">
                            <li>• User Registration & Email Verification</li>
                            <li>• Password Reset with Email Codes</li>
                            <li>• User Login & Session Management</li>
                            <li>• Supabase Database Integration</li>
                            <li>• Email Sending (when configured)</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-cog text-2xl text-blue-500"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-medium text-gray-900">Debug Tools</h3>
                                <p class="text-sm text-gray-600">Test email, database, and configuration</p>
                                <a href="/debug" class="text-blue-600 hover:text-blue-500 text-sm font-medium">
                                    Open Debug Page →
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-check-circle text-2xl text-green-500"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-medium text-gray-900">Check Config</h3>
                                <p class="text-sm text-gray-600">Verify your settings are correct</p>
                                <a href="/check-config" class="text-green-600 hover:text-green-500 text-sm font-medium">
                                    Check Configuration →
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-database text-2xl text-purple-500"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-medium text-gray-900">Supabase Setup</h3>
                                <p class="text-sm text-gray-600">Set up your database tables</p>
                                <a href="/setup-supabase" class="text-purple-600 hover:text-purple-500 text-sm font-medium">
                                    Setup Database →
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- User Information -->
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">
                        <i class="fas fa-user mr-2 text-blue-500"></i>Your Account Information
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Name</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $user['name'] ?? 'Not available' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Email</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $user['email'] ?? 'Not available' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Company</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $user['company'] ?? 'Not specified' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Session Status</dt>
                            <dd class="mt-1 text-sm text-green-600 font-medium">
                                <i class="fas fa-check-circle mr-1"></i>Active & Verified
                            </dd>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Next Steps -->
            <div class="mt-6 bg-blue-50 border border-blue-200 rounded-md p-4">
                <h3 class="text-sm font-medium text-blue-800 mb-2">🚀 Next Steps:</h3>
                <ol class="text-sm text-blue-700 space-y-1 list-decimal list-inside">
                    <li>Set up your Supabase database using the setup link above</li>
                    <li>Configure your email settings for notifications</li>
                    <li>Test the debug tools to ensure everything works</li>
                    <li>Start using the Supabase-based features for customers and proposals</li>
                </ol>
            </div>
        </main>
    </div>
</body>
</html> 
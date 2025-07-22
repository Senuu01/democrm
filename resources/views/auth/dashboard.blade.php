<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard - Connectly</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen">
    <nav class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <h1 class="text-2xl font-bold text-indigo-600">Connectly</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="text-right">
                        <div class="text-gray-900 font-medium">{{ $user_name }}</div>
                        <div class="text-gray-600 text-sm">{{ $user_email }}</div>
                        @if($user_company)
                            <div class="text-gray-500 text-xs">{{ $user_company }}</div>
                        @endif
                    </div>
                    <form method="POST" action="{{ route('auth.logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">
                            Logout
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <div class="px-4 py-6 sm:px-0">
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="px-6 py-4">
                    <h2 class="text-2xl font-bold text-gray-900 mb-4">Welcome back, {{ $user_name }}!</h2>
                    
                    <p class="text-gray-600 mb-6">You're successfully logged in with: <strong>{{ $user_email }}</strong>
                    @if($user_company)
                        from <strong>{{ $user_company }}</strong>
                    @endif
                    </p>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="bg-indigo-50 p-6 rounded-lg">
                            <h3 class="text-lg font-semibold text-indigo-900">Customers</h3>
                            <p class="text-indigo-600">Manage your customers</p>
                        </div>
                        
                        <div class="bg-green-50 p-6 rounded-lg">
                            <h3 class="text-lg font-semibold text-green-900">Proposals</h3>
                            <p class="text-green-600">Create and track proposals</p>
                        </div>
                        
                        <div class="bg-blue-50 p-6 rounded-lg">
                            <h3 class="text-lg font-semibold text-blue-900">Analytics</h3>
                            <p class="text-blue-600">View your business metrics</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
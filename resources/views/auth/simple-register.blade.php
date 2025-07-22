<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Register - Connectly</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gradient-to-br from-blue-900 via-blue-800 to-indigo-900 flex items-center justify-center p-4">
    <div class="w-full max-w-md bg-white rounded-lg shadow-xl p-8">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Connectly</h1>
            <p class="text-gray-600">Create your account</p>
        </div>

        @if($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                @foreach($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('supabase.register') }}" class="space-y-6">
            @csrf
            
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Name</label>
                <input id="name" name="name" type="text" required 
                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                       value="{{ old('name') }}">
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                <input id="email" name="email" type="email" required 
                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"
                       value="{{ old('email') }}">
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                <input id="password" name="password" type="password" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-2">Confirm Password</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
            </div>

            <button type="submit" 
                    class="w-full bg-indigo-600 text-white py-2 px-4 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition duration-200">
                Create Account
            </button>
        </form>

        <div class="mt-6 text-center">
            <a href="{{ route('supabase.login') }}" class="text-indigo-600 hover:text-indigo-500">
                Already have an account? Sign in
            </a>
        </div>

        <div class="mt-4 text-center">
            <a href="/" class="text-gray-500 hover:text-gray-700">
                ← Back to home
            </a>
        </div>
    </div>
</body>
</html>
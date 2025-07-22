<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verify Code - Connectly</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gradient-to-br from-blue-900 via-blue-800 to-indigo-900 flex items-center justify-center p-4">
    <div class="w-full max-w-md bg-white rounded-lg shadow-xl p-8">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Enter Code</h1>
            <p class="text-gray-600">Check your email for the 6-digit code</p>
        </div>

        @if($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                @foreach($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('auth.verify') }}" class="space-y-6">
            @csrf
            
            <div>
                <label for="code" class="block text-sm font-medium text-gray-700 mb-2">6-Digit Code</label>
                <input id="code" name="code" type="text" required maxlength="6"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 text-center text-2xl"
                       placeholder="000000">
            </div>

            <button type="submit" 
                    class="w-full bg-indigo-600 text-white py-2 px-4 rounded-md hover:bg-indigo-700 transition duration-200">
                Verify Code
            </button>
        </form>

        <div class="mt-6 text-center">
            <a href="{{ route('login') }}" class="text-indigo-600 hover:text-indigo-500">← Use different email</a>
        </div>
    </div>
</body>
</html>
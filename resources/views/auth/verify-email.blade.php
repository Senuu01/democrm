<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verify Email - Connectly CRM</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full space-y-8 p-8">
        <div class="text-center">
            <h1 class="text-3xl font-bold text-indigo-600 mb-2">Connectly CRM</h1>
            <h2 class="text-xl font-semibold text-gray-900 mb-2">Verify your email</h2>
            <p class="text-sm text-gray-600">We've sent a verification code to <strong>{{ session('email') }}</strong></p>
        </div>

        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                <ul class="list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('auth.verify-email.post') }}" class="space-y-4">
            @csrf
            
            <div>
                <label for="verification_code" class="block text-sm font-medium text-gray-700">Verification Code</label>
                <input type="text" name="verification_code" id="verification_code" required maxlength="6" 
                       class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 text-center text-2xl tracking-widest"
                       placeholder="000000"
                       pattern="[0-9]{6}"
                       autocomplete="off">
                <p class="mt-1 text-sm text-gray-500">Enter the 6-digit code sent to your email</p>
            </div>

            <div>
                <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Verify Email
                </button>
            </div>
        </form>

        <div class="text-center">
            <p class="text-sm text-gray-600">
                Didn't receive the code?
                <a href="{{ route('auth.email-register') }}" class="font-medium text-indigo-600 hover:text-indigo-500">
                    Try registering again
                </a>
            </p>
        </div>
    </div>

    <script>
        // Auto-focus and auto-format verification code input
        document.getElementById('verification_code').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, ''); // Remove non-digits
            if (value.length > 6) value = value.substring(0, 6);
            e.target.value = value;
        });
    </script>
</body>
</html>
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Connectly') }}</title>
    
    <!-- Simple inline Tailwind styles as fallback -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .animate-fadeIn { 
            animation: fadeIn 0.8s ease-in-out; 
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="font-sans antialiased">
    @yield('content')
</body>
</html>
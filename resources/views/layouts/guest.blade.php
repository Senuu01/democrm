<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        <script src="https://cdn.tailwindcss.com"></script>
        <style>
            body {
                min-height: 100vh;
                background: linear-gradient(135deg, #0a224e 0%, #1e3a8a 60%, #2563eb 100%);
                overflow-x: hidden;
            }
            .animated-bg {
                position: fixed;
                top: 0; left: 0; width: 100vw; height: 100vh;
                z-index: 0;
                pointer-events: none;
            }
            .float-shape {
                position: absolute;
                border-radius: 50%;
                opacity: 0.13;
                filter: blur(12px);
                animation: float 8s ease-in-out infinite;
            }
            .float-shape.shape1 {
                width: 320px; height: 320px; left: 5vw; top: 10vh; background: #2563eb; animation-delay: 0s;
            }
            .float-shape.shape2 {
                width: 400px; height: 400px; right: 8vw; bottom: 8vh; background: #1e3a8a; animation-delay: 2s;
            }
            .float-shape.shape3 {
                width: 180px; height: 180px; left: 50vw; top: 40vh; background: #60a5fa; animation-delay: 1s;
            }
            @keyframes float {
                0% { transform: translateY(0); }
                50% { transform: translateY(-30px); }
                100% { transform: translateY(0); }
            }
            .brand-animated {
                font-size: 2.5rem;
                font-weight: 900;
                text-align: center;
                background: linear-gradient(90deg, #2563eb 0%, #06b6d4 100%);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
                background-clip: text;
                text-fill-color: transparent;
                letter-spacing: 0.06em;
                margin-bottom: 2rem;
                opacity: 1;
                text-shadow: 0 0 6px #38bdf8, 0 0 12px #2563eb;
                filter: none;
                animation: brand-glow 2.2s ease-in-out infinite alternate;
            }
            @keyframes brand-glow {
                0% {
                    text-shadow: 0 0 2px #38bdf8, 0 0 6px #2563eb;
                }
                100% {
                    text-shadow: 0 0 12px #38bdf8, 0 0 18px #2563eb;
                }
            }
        </style>
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="animated-bg">
            <div class="float-shape shape1"></div>
            <div class="float-shape shape2"></div>
            <div class="float-shape shape3"></div>
        </div>
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 relative z-10">
            <div class="brand-animated">Connectly</div>
            <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white bg-opacity-90 shadow-md overflow-hidden sm:rounded-lg">
                @if(session('success'))
                    <div class="mb-4 text-green-600 font-semibold">
                        {{ session('success') }}
                    </div>
                @endif
                {{ $slot }}
            </div>
        </div>
    </body>
</html>

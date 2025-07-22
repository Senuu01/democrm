<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Connectly - Modern CRM Solution</title>
            <script src="https://cdn.tailwindcss.com"></script>
            <style>
        body {
            overflow-x: hidden;
        }
        .bg-gradient-to-r {
            background-image: linear-gradient(to right, #e0f2fe, #ffffff);
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        @keyframes slideUp {
            from { transform: translateY(50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        @keyframes slideIn {
            from { transform: translateX(-50px); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        .animate-fadeIn { animation: fadeIn 1s ease-out forwards; }
        .animate-slideUp { animation: slideUp 0.8s ease-out forwards; }
        .animate-slideIn { animation: slideIn 0.8s ease-out forwards; }
        .hero-section {
            background-color: #f8faff; /* Light blue background */
        }
        .text-indigo-800 { color: #312e81; } /* Darker indigo for headings */
        .text-indigo-600 { color: #4f46e5; } /* Standard indigo for links/buttons */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: #fff; /* White background for the loading screen */
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            opacity: 1;
            transition: opacity 0.5s ease-out;
        }
        .loading-overlay.hidden {
            opacity: 0;
            pointer-events: none;
        }
        .animate-fade-in {
            animation: fadeIn 1s ease-in;
        }
        .animate-slide-up {
            animation: slideUp 0.8s ease-out;
        }
        .animate-slide-in {
            animation: slideIn 0.8s ease-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        @keyframes slideUp {
            from { transform: translateY(50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        @keyframes slideIn {
            from { transform: translateX(-50px); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        .gradient-bg {
            background: linear-gradient(135deg, #4F46E5 0%, #7C3AED 100%);
        }
            </style>
    </head>
<body class="font-sans antialiased">
    <div id="loading-overlay" class="loading-overlay">
        <video width="200" height="200" autoplay loop muted playsinline>
            <source src="{{ asset('assets/Crm Implementation.mp4') }}" type="video/mp4">
            Your browser does not support the video tag.
        </video>
    </div>

    <script>
        window.addEventListener('load', function() {
            const loadingOverlay = document.getElementById('loading-overlay');
            loadingOverlay.classList.add('hidden');
            setTimeout(() => {
                loadingOverlay.style.display = 'none';
            }, 500); // Allow time for fade-out animation
        });
    </script>

    <div class="min-h-screen bg-gradient-to-r from-blue-50 to-white">
        <!-- Navigation -->
        <nav class="bg-white shadow-sm">
            <div class="w-full px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">
                <a href="/" class="text-3xl font-extrabold text-indigo-600 animate-fadeIn mr-auto" style="animation-delay: 0.2s;">Connectly</a>
                <div class="flex items-center space-x-4">
                    <a href="{{ route('auth.login') }}" class="text-gray-600 hover:text-gray-900 animate-fadeIn" style="animation-delay: 0.4s;">Login</a>
                    <a href="{{ route('auth.register') }}" class="bg-indigo-600 text-white px-5 py-2 rounded-lg hover:bg-indigo-700 transition duration-300 animate-fadeIn" style="animation-delay: 0.6s;">Get Started</a>
                </div>
            </div>
                </nav>

        <!-- Hero Section -->
        <div class="hero-section py-20 px-4 sm:px-6 lg:px-8 flex flex-col md:flex-row items-center justify-between">
            <div class="md:w-1/2 text-center md:text-left mb-10 md:mb-0">
                <h1 class="text-5xl md:text-6xl font-extrabold text-indigo-800 leading-tight animate-slideIn" style="animation-delay: 0.8s;">
                    Transform Your <br class="hidden md:block"/><span class="text-indigo-600">Customer Relationships</span>
                </h1>
                <p class="mt-4 text-lg text-gray-700 animate-slideUp" style="animation-delay: 1s;">
                    Streamline your business operations with our modern CRM solution. Manage customers, track proposals, and grow your business efficiently.
                </p>
                <div class="mt-8 space-x-4 animate-slideUp" style="animation-delay: 1.2s;">
                    <a href="{{ route('register') }}" class="bg-indigo-600 text-white px-8 py-3 rounded-lg text-lg font-semibold hover:bg-indigo-700 transition duration-300 shadow-lg">Get Started</a>
                    <a href="#features" class="bg-blue-100 text-indigo-600 px-8 py-3 rounded-lg text-lg font-semibold hover:bg-blue-200 transition duration-300">Learn More</a>
                </div>
            </div>
            <div class="md:w-1/2 flex justify-center md:justify-end">
                <video width="600" height="auto" autoplay loop muted playsinline class="rounded-lg shadow-xl">
                    <source src="{{ asset('assets/Crm Implementation.mp4') }}" type="video/mp4">
                    Your browser does not support the video tag.
                </video>
            </div>
        </div>

        <!-- Features Section -->
        <div id="features" class="py-12 bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="lg:text-center">
                    <h2 class="text-base text-indigo-600 font-semibold tracking-wide uppercase">Features</h2>
                    <p class="mt-2 text-3xl leading-8 font-extrabold tracking-tight text-gray-900 sm:text-4xl">
                        Everything you need to manage your business
                    </p>
                </div>

                <div class="mt-10">
                    <div class="space-y-10 md:space-y-0 md:grid md:grid-cols-2 md:gap-x-8 md:gap-y-10">
                        <div class="relative animate-slide-in">
                            <div class="absolute flex items-center justify-center h-12 w-12 rounded-md bg-indigo-500 text-white">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                            </div>
                            <div class="ml-16">
                                <h3 class="text-lg leading-6 font-medium text-gray-900">Customer Management</h3>
                                <p class="mt-2 text-base text-gray-500">
                                    Keep track of all your customer interactions and information in one place.
                                </p>
                            </div>
                        </div>

                        <div class="relative animate-slide-in">
                            <div class="absolute flex items-center justify-center h-12 w-12 rounded-md bg-indigo-500 text-white">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                            </div>
                            <div class="ml-16">
                                <h3 class="text-lg leading-6 font-medium text-gray-900">Proposal Management</h3>
                                <p class="mt-2 text-base text-gray-500">
                                    Create and track professional proposals with ease.
                                </p>
                            </div>
                        </div>

                        <div class="relative animate-slide-in">
                            <div class="absolute flex items-center justify-center h-12 w-12 rounded-md bg-indigo-500 text-white">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                            </div>
                            <div class="ml-16">
                                <h3 class="text-lg leading-6 font-medium text-gray-900">Invoice Tracking</h3>
                                <p class="mt-2 text-base text-gray-500">
                                    Manage your invoices and payments efficiently.
                                </p>
                            </div>
                        </div>

                        <div class="relative animate-slide-in">
                            <div class="absolute flex items-center justify-center h-12 w-12 rounded-md bg-indigo-500 text-white">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                            </div>
                            <div class="ml-16">
                                <h3 class="text-lg leading-6 font-medium text-gray-900">Analytics Dashboard</h3>
                                <p class="mt-2 text-base text-gray-500">
                                    Get insights into your business performance with detailed analytics.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <footer class="bg-gray-50">
            <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
                <p class="text-center text-base text-gray-400">
                    &copy; 2024 Connectly. All rights reserved.
                </p>
            </div>
        </footer>
    </div>
    </body>
</html>

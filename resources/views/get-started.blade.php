<x-guest-layout>
    <div class="relative sm:flex sm:justify-center sm:items-center min-h-screen bg-gray-100 dark:bg-gray-900 selection:bg-blue-500 selection:text-white">
        <div class="max-w-7xl mx-auto p-6 lg:p-8">
            <div class="flex justify-center">
                <x-application-logo class="w-20 h-20 fill-current text-gray-500" />
            </div>

            <div class="mt-16 text-center">
                <h1 class="text-5xl font-bold text-gray-900 dark:text-white leading-tight mb-4 animate-fade-in-down">
                    Welcome to Connectly
                </h1>
                <p class="text-xl text-gray-600 dark:text-gray-400 mb-8 animate-fade-in-up">
                    Your complete business management solution for seamless operations.
                </p>
                <div class="space-x-4 animate-fade-in-up delay-200">
                    <a href="{{ route('login') }}" class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition ease-in-out duration-300 transform hover:scale-105">
                        Log In
                    </a>
                    <a href="{{ route('register') }}" class="inline-flex items-center px-6 py-3 border border-blue-600 text-base font-medium rounded-md text-blue-600 bg-white hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition ease-in-out duration-300 transform hover:scale-105">
                        Register
                    </a>
                </div>
            </div>

            <div class="flex justify-center mt-16 px-0 sm:items-center sm:justify-between">
                <div class="text-center text-sm text-gray-500 dark:text-gray-400 sm:text-left">
                    <div class="flex items-center gap-4">
                        <a href="https://connectly.com/about" class="group inline-flex items-center hover:text-gray-700 dark:hover:text-white focus:outline-none focus:rounded-sm focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            About Us
                        </a>
                        <a href="https://connectly.com/contact" class="group inline-flex items-center hover:text-gray-700 dark:hover:text-white focus:outline-none focus:rounded-sm focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            Contact
                        </a>
                    </div>
                </div>

                <div class="ml-4 text-center text-sm text-gray-500 dark:text-gray-400 sm:text-right sm:ml-0">
                    Connectly CRM &copy; {{ date('Y') }} All rights reserved.
                </div>
            </div>
        </div>
    </div>

    <style>
        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-fade-in-down {
            animation: fadeInDown 1s ease-out forwards;
        }

        .animate-fade-in-up {
            animation: fadeInUp 1s ease-out forwards;
        }

        .delay-200 {
            animation-delay: 0.2s;
        }
    </style>
</x-guest-layout> 
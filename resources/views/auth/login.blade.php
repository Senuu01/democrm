<x-guest-layout>
    <div class="min-h-screen flex font-sans antialiased bg-gray-100">
        <!-- Left Section: Blue Background with Text -->
        <div class="flex-grow flex flex-col justify-center items-start p-16 bg-gradient-to-br from-blue-700 to-blue-900 text-white relative overflow-hidden">
            <!-- Abstract shapes -->
            <div class="absolute top-0 left-0 w-full h-full opacity-20">
                <svg class="w-full h-full" viewBox="0 0 100 100" preserveAspectRatio="none">
                    <path d="M0,0 C20,30 40,0 60,30 S80,0 100,30 V0 H0 Z" fill="currentColor" style="color: rgba(255,255,255,0.1);"></path>
                    <path d="M0,100 C20,70 40,100 60,70 S80,100 100,70 V100 H0 Z" fill="currentColor" style="color: rgba(255,255,255,0.05);"></path>
                </svg>
            </div>

            <div class="relative z-10 text-left">
                <h1 class="text-5xl font-extrabold mb-6 leading-tight">Hello Connectly! 👋</h1>
                <p class="text-lg leading-relaxed max-w-sm">
                    Skip repetitive and manual sales-marketing tasks. Get highly productive through automation and save tons of time!
                </p>
            </div>

            <div class="relative z-10 text-sm opacity-80 mt-auto">
                © 2024 Connectly. All rights reserved.
            </div>
        </div>

        <!-- Right Section: Login Form -->
        <div class="w-[550px] flex-shrink-0 flex items-center justify-center bg-white p-16">
            <div class="w-full">
                <h2 class="text-3xl font-semibold text-gray-800 mb-10">Connectly</h2>

                <h3 class="text-2xl font-bold text-gray-900 mb-2">Welcome Back!</h3>
                <p class="text-gray-600 mb-8 text-sm leading-relaxed">
                    Don't have an account? <a href="{{ route('register') }}" class="text-blue-600 hover:underline font-medium">Create a new account now</a>, it's FREE! Takes less than a minute.
                </p>

                <!-- Session Status -->
                <x-auth-session-status class="mb-4" :status="session('status')" />

                <form method="POST" action="{{ route('login') }}">
                    @csrf

                    <!-- Email Address -->
                    <div class="mb-4">
                        <x-text-input id="email" class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 text-gray-800 placeholder-gray-400" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" placeholder="hisalim.ux@gmail.com" style="box-shadow: none;" />
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>

                    <!-- Password -->
                    <div class="mb-6">
                        <x-text-input id="password" class="block w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500 text-gray-800 placeholder-gray-400"
                                type="password"
                                name="password"
                                required autocomplete="current-password" placeholder="Password" style="box-shadow: none;" />
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>

                    <div class="flex items-center justify-end mt-4">
                        @if (Route::has('password.request'))
                            <a class="text-sm text-blue-600 hover:underline" href="{{ route('password.request') }}">
                                Forgot password?
                            </a>
                        @endif
                    </div>

                    <x-primary-button class="w-full justify-center bg-gray-900 text-white font-bold py-3 px-4 rounded-lg mt-8 focus:outline-none focus:ring-0 hover:bg-gray-800 transition duration-300 shadow">
                        LOGIN NOW
                    </x-primary-button>
                </form>

                <div class="mt-8 text-center">
                    <div class="relative flex items-center justify-center mb-8">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-gray-200"></div>
                        </div>
                        <div class="relative bg-white px-4 text-sm text-gray-500 uppercase font-medium">
                            OR
                        </div>
                    </div>
                    <button class="w-full flex items-center justify-center bg-white border border-gray-300 text-gray-700 font-bold py-3 px-4 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-0 transition duration-300 shadow">
                        <img src="https://www.google.com/favicon.ico" alt="Google logo" class="w-5 h-5 mr-2">
                        Login with Google
                    </button>
                </div>
            </div>
        </div>
    </div>
</x-guest-layout>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Settings - Connectly CRM</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen">
        <!-- Header -->
        <header class="bg-white shadow">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <a href="{{ route('dashboard') }}" class="text-xl font-semibold text-gray-900 hover:text-blue-600">
                            <i class="fas fa-arrow-left mr-2"></i>Connectly CRM
                        </a>
                    </div>
                    <div class="flex items-center space-x-4">
                        <span class="text-gray-700">{{ session('user_data.name', 'User') }}</span>
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
        <main class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8" x-data="settingsApp()">
            <!-- Page Header -->
            <div class="mb-8">
                <h1 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                    <i class="fas fa-user-cog text-blue-500 mr-3"></i>User Settings
                </h1>
                <p class="mt-1 text-sm text-gray-500">Manage your account settings and preferences</p>
            </div>

            <!-- Alerts -->
            @if(session('success'))
                <div class="bg-green-50 border-l-4 border-green-400 p-4 mb-6" x-data="{ show: true }" x-show="show">
                    <div class="flex justify-between">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-check-circle text-green-400"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-green-700">{{ session('success') }}</p>
                            </div>
                        </div>
                        <button @click="show = false" class="text-green-400 hover:text-green-600">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            @endif

            @if(session('error'))
                <div class="bg-red-50 border-l-4 border-red-400 p-4 mb-6" x-data="{ show: true }" x-show="show">
                    <div class="flex justify-between">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-exclamation-circle text-red-400"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-red-700">{{ session('error') }}</p>
                            </div>
                        </div>
                        <button @click="show = false" class="text-red-400 hover:text-red-600">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            @endif

            <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                <!-- Tab Navigation -->
                <div class="border-b border-gray-200">
                    <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                        <button @click="activeTab = 'profile'" 
                                :class="activeTab === 'profile' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                            <i class="fas fa-user mr-2"></i>Profile
                        </button>
                        <button @click="activeTab = 'security'" 
                                :class="activeTab === 'security' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                            <i class="fas fa-shield-alt mr-2"></i>Security
                        </button>
                        <button @click="activeTab = 'notifications'" 
                                :class="activeTab === 'notifications' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                            <i class="fas fa-bell mr-2"></i>Notifications
                        </button>
                        <button @click="activeTab = 'preferences'" 
                                :class="activeTab === 'preferences' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                            <i class="fas fa-cog mr-2"></i>Preferences
                        </button>
                        <button @click="activeTab = 'data'" 
                                :class="activeTab === 'data' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                            <i class="fas fa-database mr-2"></i>Data & Privacy
                        </button>
                    </nav>
                </div>

                <!-- Tab Content -->
                <div class="p-6">
                    <!-- Profile Tab -->
                    <div x-show="activeTab === 'profile'" x-transition>
                        <form method="POST" action="{{ route('settings.profile') }}" enctype="multipart/form-data">
                            @csrf
                            <div class="space-y-6">
                                <div>
                                    <h3 class="text-lg font-medium text-gray-900">Profile Information</h3>
                                    <p class="mt-1 text-sm text-gray-600">Update your personal information and profile picture.</p>
                                </div>

                                <!-- Profile Picture -->
                                <div class="flex items-center space-x-6">
                                    <div class="shrink-0">
                                        @if($user['profile_image'] ?? false)
                                            <img class="h-16 w-16 object-cover rounded-full" src="{{ asset('storage/profiles/' . $user['profile_image']) }}" alt="Profile">
                                        @else
                                            <div class="h-16 w-16 bg-blue-500 rounded-full flex items-center justify-center">
                                                <span class="text-xl font-bold text-white">{{ strtoupper(substr($user['name'] ?? 'U', 0, 1)) }}</span>
                                            </div>
                                        @endif
                                    </div>
                                    <div>
                                        <label for="profile_image" class="block text-sm font-medium text-gray-700">Profile Picture</label>
                                        <input type="file" name="profile_image" id="profile_image" accept="image/*" 
                                               class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                                        @error('profile_image')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>

                                <!-- Name -->
                                <div>
                                    <label for="name" class="block text-sm font-medium text-gray-700">Full Name</label>
                                    <input type="text" name="name" id="name" value="{{ old('name', $user['name'] ?? '') }}" required
                                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                    @error('name')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Email (Read-only) -->
                                <div>
                                    <label for="email" class="block text-sm font-medium text-gray-700">Email Address</label>
                                    <input type="email" id="email" value="{{ $user['email'] ?? '' }}" disabled
                                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm bg-gray-50 text-gray-500">
                                    <p class="mt-1 text-sm text-gray-500">Email cannot be changed. Contact support if needed.</p>
                                </div>

                                <!-- Company -->
                                <div>
                                    <label for="company" class="block text-sm font-medium text-gray-700">Company</label>
                                    <input type="text" name="company" id="company" value="{{ old('company', $user['company'] ?? '') }}"
                                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                    @error('company')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Phone -->
                                <div>
                                    <label for="phone" class="block text-sm font-medium text-gray-700">Phone Number</label>
                                    <input type="tel" name="phone" id="phone" value="{{ old('phone', $user['phone'] ?? '') }}"
                                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                    @error('phone')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Timezone -->
                                <div>
                                    <label for="timezone" class="block text-sm font-medium text-gray-700">Timezone</label>
                                    <select name="timezone" id="timezone" required
                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                        <option value="America/New_York" {{ old('timezone', $user['timezone'] ?? '') === 'America/New_York' ? 'selected' : '' }}>Eastern Time (ET)</option>
                                        <option value="America/Chicago" {{ old('timezone', $user['timezone'] ?? '') === 'America/Chicago' ? 'selected' : '' }}>Central Time (CT)</option>
                                        <option value="America/Denver" {{ old('timezone', $user['timezone'] ?? '') === 'America/Denver' ? 'selected' : '' }}>Mountain Time (MT)</option>
                                        <option value="America/Los_Angeles" {{ old('timezone', $user['timezone'] ?? '') === 'America/Los_Angeles' ? 'selected' : '' }}>Pacific Time (PT)</option>
                                        <option value="Europe/London" {{ old('timezone', $user['timezone'] ?? '') === 'Europe/London' ? 'selected' : '' }}>London (GMT)</option>
                                        <option value="Europe/Paris" {{ old('timezone', $user['timezone'] ?? '') === 'Europe/Paris' ? 'selected' : '' }}>Paris (CET)</option>
                                        <option value="Asia/Tokyo" {{ old('timezone', $user['timezone'] ?? '') === 'Asia/Tokyo' ? 'selected' : '' }}>Tokyo (JST)</option>
                                        <option value="Australia/Sydney" {{ old('timezone', $user['timezone'] ?? '') === 'Australia/Sydney' ? 'selected' : '' }}>Sydney (AEDT)</option>
                                    </select>
                                    @error('timezone')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="flex justify-end">
                                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                        <i class="fas fa-save mr-2"></i>Update Profile
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Security Tab -->
                    <div x-show="activeTab === 'security'" x-transition>
                        <div class="space-y-8">
                            <div>
                                <h3 class="text-lg font-medium text-gray-900">Security Settings</h3>
                                <p class="mt-1 text-sm text-gray-600">Manage your password and account security.</p>
                            </div>

                            <!-- Change Password -->
                            <form method="POST" action="{{ route('settings.password') }}">
                                @csrf
                                <div class="bg-gray-50 rounded-lg p-6">
                                    <h4 class="text-md font-medium text-gray-900 mb-4">Change Password</h4>
                                    <div class="space-y-4">
                                        <div>
                                            <label for="current_password" class="block text-sm font-medium text-gray-700">Current Password</label>
                                            <input type="password" name="current_password" id="current_password" required
                                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                            @error('current_password')
                                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                            @enderror
                                        </div>

                                        <div>
                                            <label for="password" class="block text-sm font-medium text-gray-700">New Password</label>
                                            <input type="password" name="password" id="password" required
                                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                            @error('password')
                                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                            @enderror
                                            <p class="mt-1 text-sm text-gray-500">Must be at least 8 characters with uppercase, lowercase, and numbers.</p>
                                        </div>

                                        <div>
                                            <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Confirm New Password</label>
                                            <input type="password" name="password_confirmation" id="password_confirmation" required
                                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                        </div>

                                        <div class="flex justify-end">
                                            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                                                <i class="fas fa-key mr-2"></i>Update Password
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>

                            <!-- Account Security Info -->
                            <div class="bg-blue-50 rounded-lg p-6">
                                <h4 class="text-md font-medium text-gray-900 mb-4">Account Security</h4>
                                <div class="space-y-3 text-sm">
                                    <div class="flex items-center">
                                        <i class="fas fa-check-circle text-green-500 mr-2"></i>
                                        <span>Email verified</span>
                                    </div>
                                    <div class="flex items-center">
                                        <i class="fas fa-shield-alt text-blue-500 mr-2"></i>
                                        <span>Account created: {{ date('M j, Y', strtotime($user['created_at'] ?? 'now')) }}</span>
                                    </div>
                                    <div class="flex items-center">
                                        <i class="fas fa-clock text-gray-500 mr-2"></i>
                                        <span>Last updated: {{ date('M j, Y', strtotime($user['updated_at'] ?? 'now')) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Notifications Tab -->
                    <div x-show="activeTab === 'notifications'" x-transition>
                        <form method="POST" action="{{ route('settings.preferences') }}">
                            @csrf
                            <div class="space-y-6">
                                <div>
                                    <h3 class="text-lg font-medium text-gray-900">Notification Preferences</h3>
                                    <p class="mt-1 text-sm text-gray-600">Choose what notifications you'd like to receive.</p>
                                </div>

                                <div class="space-y-4">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <h4 class="text-sm font-medium text-gray-900">Email Notifications</h4>
                                            <p class="text-sm text-gray-600">Receive notifications via email</p>
                                        </div>
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="checkbox" name="email_notifications" value="1" 
                                                   {{ ($preferences['email_notifications'] ?? true) ? 'checked' : '' }}
                                                   class="sr-only peer">
                                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                        </label>
                                    </div>

                                    <div class="flex items-center justify-between">
                                        <div>
                                            <h4 class="text-sm font-medium text-gray-900">Customer Notifications</h4>
                                            <p class="text-sm text-gray-600">New customers and updates</p>
                                        </div>
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="checkbox" name="customer_notifications" value="1" 
                                                   {{ ($preferences['customer_notifications'] ?? true) ? 'checked' : '' }}
                                                   class="sr-only peer">
                                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                        </label>
                                    </div>

                                    <div class="flex items-center justify-between">
                                        <div>
                                            <h4 class="text-sm font-medium text-gray-900">Proposal Notifications</h4>
                                            <p class="text-sm text-gray-600">Proposal status changes</p>
                                        </div>
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="checkbox" name="proposal_notifications" value="1" 
                                                   {{ ($preferences['proposal_notifications'] ?? true) ? 'checked' : '' }}
                                                   class="sr-only peer">
                                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                        </label>
                                    </div>

                                    <div class="flex items-center justify-between">
                                        <div>
                                            <h4 class="text-sm font-medium text-gray-900">Invoice Notifications</h4>
                                            <p class="text-sm text-gray-600">Invoice creation and updates</p>
                                        </div>
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="checkbox" name="invoice_notifications" value="1" 
                                                   {{ ($preferences['invoice_notifications'] ?? true) ? 'checked' : '' }}
                                                   class="sr-only peer">
                                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                        </label>
                                    </div>

                                    <div class="flex items-center justify-between">
                                        <div>
                                            <h4 class="text-sm font-medium text-gray-900">Payment Notifications</h4>
                                            <p class="text-sm text-gray-600">Payment confirmations</p>
                                        </div>
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="checkbox" name="payment_notifications" value="1" 
                                                   {{ ($preferences['payment_notifications'] ?? true) ? 'checked' : '' }}
                                                   class="sr-only peer">
                                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                        </label>
                                    </div>

                                    <div class="flex items-center justify-between">
                                        <div>
                                            <h4 class="text-sm font-medium text-gray-900">Marketing Emails</h4>
                                            <p class="text-sm text-gray-600">Product updates and tips</p>
                                        </div>
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="checkbox" name="marketing_emails" value="1" 
                                                   {{ ($preferences['marketing_emails'] ?? false) ? 'checked' : '' }}
                                                   class="sr-only peer">
                                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                        </label>
                                    </div>

                                    <div class="flex items-center justify-between">
                                        <div>
                                            <h4 class="text-sm font-medium text-gray-900">Weekly Reports</h4>
                                            <p class="text-sm text-gray-600">Weekly business summaries</p>
                                        </div>
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="checkbox" name="weekly_reports" value="1" 
                                                   {{ ($preferences['weekly_reports'] ?? true) ? 'checked' : '' }}
                                                   class="sr-only peer">
                                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                        </label>
                                    </div>
                                </div>

                                <div class="flex justify-end">
                                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                        <i class="fas fa-save mr-2"></i>Save Preferences
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Preferences Tab -->
                    <div x-show="activeTab === 'preferences'" x-transition>
                        <form method="POST" action="{{ route('settings.preferences') }}">
                            @csrf
                            <div class="space-y-6">
                                <div>
                                    <h3 class="text-lg font-medium text-gray-900">Application Preferences</h3>
                                    <p class="mt-1 text-sm text-gray-600">Customize your application experience.</p>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <!-- Language -->
                                    <div>
                                        <label for="language" class="block text-sm font-medium text-gray-700">Language</label>
                                        <select name="language" id="language" required
                                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                            <option value="en" {{ ($preferences['language'] ?? 'en') === 'en' ? 'selected' : '' }}>English</option>
                                            <option value="es" {{ ($preferences['language'] ?? 'en') === 'es' ? 'selected' : '' }}>Español</option>
                                            <option value="fr" {{ ($preferences['language'] ?? 'en') === 'fr' ? 'selected' : '' }}>Français</option>
                                            <option value="de" {{ ($preferences['language'] ?? 'en') === 'de' ? 'selected' : '' }}>Deutsch</option>
                                            <option value="it" {{ ($preferences['language'] ?? 'en') === 'it' ? 'selected' : '' }}>Italiano</option>
                                        </select>
                                    </div>

                                    <!-- Currency -->
                                    <div>
                                        <label for="currency" class="block text-sm font-medium text-gray-700">Currency</label>
                                        <select name="currency" id="currency" required
                                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                            <option value="USD" {{ ($preferences['currency'] ?? 'USD') === 'USD' ? 'selected' : '' }}>US Dollar (USD)</option>
                                            <option value="EUR" {{ ($preferences['currency'] ?? 'USD') === 'EUR' ? 'selected' : '' }}>Euro (EUR)</option>
                                            <option value="GBP" {{ ($preferences['currency'] ?? 'USD') === 'GBP' ? 'selected' : '' }}>British Pound (GBP)</option>
                                            <option value="CAD" {{ ($preferences['currency'] ?? 'USD') === 'CAD' ? 'selected' : '' }}>Canadian Dollar (CAD)</option>
                                            <option value="AUD" {{ ($preferences['currency'] ?? 'USD') === 'AUD' ? 'selected' : '' }}>Australian Dollar (AUD)</option>
                                        </select>
                                    </div>

                                    <!-- Date Format -->
                                    <div>
                                        <label for="date_format" class="block text-sm font-medium text-gray-700">Date Format</label>
                                        <select name="date_format" id="date_format" required
                                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                            <option value="MM/DD/YYYY" {{ ($preferences['date_format'] ?? 'MM/DD/YYYY') === 'MM/DD/YYYY' ? 'selected' : '' }}>MM/DD/YYYY</option>
                                            <option value="DD/MM/YYYY" {{ ($preferences['date_format'] ?? 'MM/DD/YYYY') === 'DD/MM/YYYY' ? 'selected' : '' }}>DD/MM/YYYY</option>
                                            <option value="YYYY-MM-DD" {{ ($preferences['date_format'] ?? 'MM/DD/YYYY') === 'YYYY-MM-DD' ? 'selected' : '' }}>YYYY-MM-DD</option>
                                        </select>
                                    </div>

                                    <!-- Theme -->
                                    <div>
                                        <label for="theme" class="block text-sm font-medium text-gray-700">Theme</label>
                                        <select name="theme" id="theme" required
                                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                            <option value="light" {{ ($preferences['theme'] ?? 'light') === 'light' ? 'selected' : '' }}>Light</option>
                                            <option value="dark" {{ ($preferences['theme'] ?? 'light') === 'dark' ? 'selected' : '' }}>Dark</option>
                                            <option value="auto" {{ ($preferences['theme'] ?? 'light') === 'auto' ? 'selected' : '' }}>Auto (System)</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="flex justify-end">
                                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                        <i class="fas fa-save mr-2"></i>Save Preferences
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Data & Privacy Tab -->
                    <div x-show="activeTab === 'data'" x-transition>
                        <div class="space-y-8">
                            <div>
                                <h3 class="text-lg font-medium text-gray-900">Data & Privacy</h3>
                                <p class="mt-1 text-sm text-gray-600">Manage your data and account privacy settings.</p>
                            </div>

                            <!-- Export Data -->
                            <div class="bg-blue-50 rounded-lg p-6">
                                <h4 class="text-md font-medium text-gray-900 mb-4">Export Your Data</h4>
                                <p class="text-sm text-gray-600 mb-4">Download a copy of all your data including profile, customers, proposals, and invoices.</p>
                                <a href="{{ route('settings.export') }}" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded inline-flex items-center">
                                    <i class="fas fa-download mr-2"></i>Export Data
                                </a>
                            </div>

                            <!-- Delete Account -->
                            <div class="bg-red-50 rounded-lg p-6">
                                <h4 class="text-md font-medium text-gray-900 mb-4">Delete Account</h4>
                                <p class="text-sm text-gray-600 mb-4">Permanently delete your account and all associated data. This action cannot be undone.</p>
                                
                                <form method="POST" action="{{ route('settings.delete') }}" x-data="{ showConfirm: false }" @submit.prevent="if(showConfirm) $el.submit()">
                                    @csrf
                                    <div x-show="!showConfirm">
                                        <button type="button" @click="showConfirm = true" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
                                            <i class="fas fa-trash mr-2"></i>Delete Account
                                        </button>
                                    </div>
                                    
                                    <div x-show="showConfirm" x-transition class="space-y-4">
                                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                                            <p class="font-bold">⚠️ Warning: This action is irreversible!</p>
                                            <p>All your data will be permanently deleted.</p>
                                        </div>
                                        
                                        <div>
                                            <label for="delete_password" class="block text-sm font-medium text-gray-700">Enter your password to confirm</label>
                                            <input type="password" name="password" id="delete_password" required
                                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-red-500 focus:border-red-500">
                                        </div>
                                        
                                        <div>
                                            <label for="delete_confirmation" class="block text-sm font-medium text-gray-700">Type "DELETE" to confirm</label>
                                            <input type="text" name="confirmation" id="delete_confirmation" required
                                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-red-500 focus:border-red-500">
                                        </div>
                                        
                                        <div class="flex space-x-3">
                                            <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
                                                <i class="fas fa-trash mr-2"></i>Permanently Delete Account
                                            </button>
                                            <button type="button" @click="showConfirm = false" class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                                                Cancel
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        function settingsApp() {
            return {
                activeTab: 'profile'
            }
        }
    </script>
</body>
</html> 
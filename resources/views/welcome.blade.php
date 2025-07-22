<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connectly CRM - Professional Customer Relationship Management</title>
    <meta name="description" content="Professional CRM system with customer management, proposals, invoicing, payments, and analytics. Built with modern technology for growing businesses.">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .feature-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        .animate-float {
            animation: float 6s ease-in-out infinite;
        }
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-lg sticky top-0 z-50" x-data="{ mobileMenuOpen: false }">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <div class="flex-shrink-0 flex items-center">
                        <i class="fas fa-users-cog text-3xl text-blue-600 mr-3"></i>
                        <span class="text-2xl font-bold text-gray-900">Connectly CRM</span>
                    </div>
                </div>
                
                <!-- Desktop Navigation -->
                <div class="hidden md:flex items-center space-x-8">
                    <a href="#features" class="text-gray-700 hover:text-blue-600 transition duration-300">Features</a>
                    <a href="#pricing" class="text-gray-700 hover:text-blue-600 transition duration-300">Pricing</a>
                    <a href="#api" class="text-gray-700 hover:text-blue-600 transition duration-300">API</a>
                    <a href="#contact" class="text-gray-700 hover:text-blue-600 transition duration-300">Contact</a>
                    <a href="{{ route('login') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition duration-300">
                        <i class="fas fa-sign-in-alt mr-2"></i>Login
                    </a>
                    <a href="{{ route('auth.email-register') }}" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg transition duration-300">
                        <i class="fas fa-user-plus mr-2"></i>Get Started
                    </a>
                </div>

                <!-- Mobile menu button -->
                <div class="md:hidden flex items-center">
                    <button @click="mobileMenuOpen = !mobileMenuOpen" class="text-gray-700 hover:text-blue-600">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Navigation -->
        <div x-show="mobileMenuOpen" x-transition class="md:hidden bg-white border-t">
            <div class="px-2 pt-2 pb-3 space-y-1">
                <a href="#features" class="block px-3 py-2 text-gray-700 hover:text-blue-600">Features</a>
                <a href="#pricing" class="block px-3 py-2 text-gray-700 hover:text-blue-600">Pricing</a>
                <a href="#api" class="block px-3 py-2 text-gray-700 hover:text-blue-600">API</a>
                <a href="#contact" class="block px-3 py-2 text-gray-700 hover:text-blue-600">Contact</a>
                <a href="{{ route('login') }}" class="block px-3 py-2 text-blue-600 font-medium">Login</a>
                <a href="{{ route('auth.email-register') }}" class="block px-3 py-2 text-green-600 font-medium">Get Started</a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="gradient-bg text-white py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                <div>
                    <h1 class="text-4xl md:text-6xl font-bold leading-tight mb-6">
                        Grow Your Business with
                        <span class="text-yellow-300">Professional CRM</span>
                    </h1>
                    <p class="text-xl md:text-2xl mb-8 text-blue-100">
                        Complete customer relationship management with proposals, invoicing, payments, and powerful analytics. Built for modern businesses.
                    </p>
                    <div class="flex flex-col sm:flex-row gap-4">
                        <a href="{{ route('auth.email-register') }}" class="bg-yellow-400 hover:bg-yellow-500 text-gray-900 px-8 py-4 rounded-lg font-semibold text-lg transition duration-300 text-center">
                            <i class="fas fa-rocket mr-2"></i>Start Free Trial
                        </a>
                        <a href="{{ route('login') }}" class="bg-white bg-opacity-20 hover:bg-opacity-30 text-white px-8 py-4 rounded-lg font-semibold text-lg transition duration-300 text-center backdrop-blur-sm">
                            <i class="fas fa-sign-in-alt mr-2"></i>Login
                        </a>
                    </div>
                    <div class="mt-8 flex items-center space-x-6 text-blue-100">
                        <div class="flex items-center">
                            <i class="fas fa-check-circle mr-2 text-yellow-300"></i>
                            <span>Free 30-day trial</span>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-check-circle mr-2 text-yellow-300"></i>
                            <span>No credit card required</span>
                        </div>
                    </div>
                </div>
                <div class="relative">
                    <div class="animate-float">
                        <div class="bg-white bg-opacity-10 backdrop-blur-sm rounded-xl p-8 shadow-2xl">
                            <div class="space-y-4">
                                <div class="flex items-center space-x-3">
                                    <div class="w-12 h-12 bg-yellow-400 rounded-lg flex items-center justify-center">
                                        <i class="fas fa-users text-gray-900 text-xl"></i>
                                    </div>
                                    <div>
                                        <h3 class="font-semibold">Customer Management</h3>
                                        <p class="text-blue-100 text-sm">Organize and track all your customers</p>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-3">
                                    <div class="w-12 h-12 bg-green-400 rounded-lg flex items-center justify-center">
                                        <i class="fas fa-file-alt text-gray-900 text-xl"></i>
                                    </div>
                                    <div>
                                        <h3 class="font-semibold">Smart Proposals</h3>
                                        <p class="text-blue-100 text-sm">Create and send professional proposals</p>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-3">
                                    <div class="w-12 h-12 bg-purple-400 rounded-lg flex items-center justify-center">
                                        <i class="fas fa-credit-card text-gray-900 text-xl"></i>
                                    </div>
                                    <div>
                                        <h3 class="font-semibold">Instant Payments</h3>
                                        <p class="text-blue-100 text-sm">Secure Stripe integration</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-gray-900 mb-4">Everything You Need to Succeed</h2>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                    Comprehensive CRM features designed to streamline your business operations and accelerate growth.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- Customer Management -->
                <div class="feature-card bg-white p-8 rounded-xl shadow-lg border border-gray-100">
                    <div class="w-16 h-16 bg-blue-100 rounded-xl flex items-center justify-center mb-6">
                        <i class="fas fa-users text-2xl text-blue-600"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-4">Customer Management</h3>
                    <p class="text-gray-600 mb-4">
                        Organize customer data, track interactions, and maintain detailed profiles with contact history.
                    </p>
                    <ul class="text-sm text-gray-500 space-y-2">
                        <li><i class="fas fa-check text-green-500 mr-2"></i>Contact management</li>
                        <li><i class="fas fa-check text-green-500 mr-2"></i>Activity tracking</li>
                        <li><i class="fas fa-check text-green-500 mr-2"></i>Advanced search & filters</li>
                    </ul>
                </div>

                <!-- Proposals -->
                <div class="feature-card bg-white p-8 rounded-xl shadow-lg border border-gray-100">
                    <div class="w-16 h-16 bg-green-100 rounded-xl flex items-center justify-center mb-6">
                        <i class="fas fa-file-alt text-2xl text-green-600"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-4">Smart Proposals</h3>
                    <p class="text-gray-600 mb-4">
                        Create professional proposals with line items, terms, and automated follow-ups.
                    </p>
                    <ul class="text-sm text-gray-500 space-y-2">
                        <li><i class="fas fa-check text-green-500 mr-2"></i>Professional templates</li>
                        <li><i class="fas fa-check text-green-500 mr-2"></i>Digital signatures</li>
                        <li><i class="fas fa-check text-green-500 mr-2"></i>Status tracking</li>
                    </ul>
                </div>

                <!-- Invoicing -->
                <div class="feature-card bg-white p-8 rounded-xl shadow-lg border border-gray-100">
                    <div class="w-16 h-16 bg-purple-100 rounded-xl flex items-center justify-center mb-6">
                        <i class="fas fa-file-invoice text-2xl text-purple-600"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-4">Automated Invoicing</h3>
                    <p class="text-gray-600 mb-4">
                        Generate and send invoices automatically with payment tracking and reminders.
                    </p>
                    <ul class="text-sm text-gray-500 space-y-2">
                        <li><i class="fas fa-check text-green-500 mr-2"></i>Automatic generation</li>
                        <li><i class="fas fa-check text-green-500 mr-2"></i>Payment reminders</li>
                        <li><i class="fas fa-check text-green-500 mr-2"></i>Overdue tracking</li>
                    </ul>
                </div>

                <!-- Payments -->
                <div class="feature-card bg-white p-8 rounded-xl shadow-lg border border-gray-100">
                    <div class="w-16 h-16 bg-yellow-100 rounded-xl flex items-center justify-center mb-6">
                        <i class="fas fa-credit-card text-2xl text-yellow-600"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-4">Secure Payments</h3>
                    <p class="text-gray-600 mb-4">
                        Integrated Stripe payments with secure checkout and automatic reconciliation.
                    </p>
                    <ul class="text-sm text-gray-500 space-y-2">
                        <li><i class="fas fa-check text-green-500 mr-2"></i>Stripe integration</li>
                        <li><i class="fas fa-check text-green-500 mr-2"></i>Multiple payment methods</li>
                        <li><i class="fas fa-check text-green-500 mr-2"></i>Automatic updates</li>
                    </ul>
                </div>

                <!-- Analytics -->
                <div class="feature-card bg-white p-8 rounded-xl shadow-lg border border-gray-100">
                    <div class="w-16 h-16 bg-red-100 rounded-xl flex items-center justify-center mb-6">
                        <i class="fas fa-chart-line text-2xl text-red-600"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-4">Business Analytics</h3>
                    <p class="text-gray-600 mb-4">
                        Comprehensive dashboards with revenue tracking, conversion rates, and insights.
                    </p>
                    <ul class="text-sm text-gray-500 space-y-2">
                        <li><i class="fas fa-check text-green-500 mr-2"></i>Revenue analytics</li>
                        <li><i class="fas fa-check text-green-500 mr-2"></i>Conversion tracking</li>
                        <li><i class="fas fa-check text-green-500 mr-2"></i>Custom reports</li>
                    </ul>
                </div>

                <!-- API -->
                <div class="feature-card bg-white p-8 rounded-xl shadow-lg border border-gray-100">
                    <div class="w-16 h-16 bg-indigo-100 rounded-xl flex items-center justify-center mb-6">
                        <i class="fas fa-code text-2xl text-indigo-600"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-4">REST API</h3>
                    <p class="text-gray-600 mb-4">
                        Complete REST API for integrations, mobile apps, and custom implementations.
                    </p>
                    <ul class="text-sm text-gray-500 space-y-2">
                        <li><i class="fas fa-check text-green-500 mr-2"></i>Full REST API</li>
                        <li><i class="fas fa-check text-green-500 mr-2"></i>API documentation</li>
                        <li><i class="fas fa-check text-green-500 mr-2"></i>SDKs available</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- API Section -->
    <section id="api" class="py-20 bg-gray-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-gray-900 mb-4">Developer-Friendly API</h2>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                    Build custom integrations and applications with our comprehensive REST API.
                </p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                <div>
                    <h3 class="text-2xl font-semibold text-gray-900 mb-6">Complete API Coverage</h3>
                    <div class="space-y-4">
                        <div class="flex items-start space-x-3">
                            <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center mt-1">
                                <i class="fas fa-users text-blue-600"></i>
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-900">Customer Management</h4>
                                <p class="text-gray-600 text-sm">Full CRUD operations, search, filtering, and bulk operations</p>
                            </div>
                        </div>
                        <div class="flex items-start space-x-3">
                            <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center mt-1">
                                <i class="fas fa-file-alt text-green-600"></i>
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-900">Proposals & Invoices</h4>
                                <p class="text-gray-600 text-sm">Create, update, status management, and payment processing</p>
                            </div>
                        </div>
                        <div class="flex items-start space-x-3">
                            <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center mt-1">
                                <i class="fas fa-chart-bar text-purple-600"></i>
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-900">Analytics & Reporting</h4>
                                <p class="text-gray-600 text-sm">Business metrics, conversion rates, and custom analytics</p>
                            </div>
                        </div>
                    </div>
                    <div class="mt-8">
                        <a href="/api/docs" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-semibold transition duration-300 inline-block">
                            <i class="fas fa-book mr-2"></i>View API Documentation
                        </a>
                    </div>
                </div>
                <div class="bg-gray-900 rounded-xl p-6 text-green-400 font-mono text-sm overflow-x-auto">
                    <div class="mb-4">
                        <span class="text-gray-500"># Login and get access token</span><br>
                        <span class="text-blue-400">curl</span> -X POST {{ url('/api/auth/login') }} \<br>
                        &nbsp;&nbsp;-H <span class="text-yellow-300">"Content-Type: application/json"</span> \<br>
                        &nbsp;&nbsp;-d <span class="text-yellow-300">'{"email":"user@example.com","password":"password"}'</span>
                    </div>
                    <div class="mb-4">
                        <span class="text-gray-500"># Get customers with search</span><br>
                        <span class="text-blue-400">curl</span> -X GET {{ url('/api/customers') }}?search=john \<br>
                        &nbsp;&nbsp;-H <span class="text-yellow-300">"Authorization: Bearer TOKEN"</span>
                    </div>
                    <div>
                        <span class="text-gray-500"># Create new customer</span><br>
                        <span class="text-blue-400">curl</span> -X POST {{ url('/api/customers') }} \<br>
                        &nbsp;&nbsp;-H <span class="text-yellow-300">"Authorization: Bearer TOKEN"</span> \<br>
                        &nbsp;&nbsp;-d <span class="text-yellow-300">'{"name":"John Doe","email":"john@example.com"}'</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-20 gradient-bg text-white">
        <div class="max-w-4xl mx-auto text-center px-4 sm:px-6 lg:px-8">
            <h2 class="text-4xl font-bold mb-6">Ready to Transform Your Business?</h2>
            <p class="text-xl mb-8 text-blue-100">
                Join thousands of businesses using Connectly CRM to manage customers, close deals, and grow revenue.
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ route('auth.email-register') }}" class="bg-yellow-400 hover:bg-yellow-500 text-gray-900 px-8 py-4 rounded-lg font-semibold text-lg transition duration-300">
                    <i class="fas fa-rocket mr-2"></i>Start Your Free Trial
                </a>
                <a href="{{ route('login') }}" class="bg-white bg-opacity-20 hover:bg-opacity-30 text-white px-8 py-4 rounded-lg font-semibold text-lg transition duration-300 backdrop-blur-sm">
                    <i class="fas fa-sign-in-alt mr-2"></i>Login to Account
                </a>
            </div>
            <p class="mt-6 text-blue-100">
                <i class="fas fa-shield-alt mr-2"></i>30-day free trial • No credit card required • Cancel anytime
            </p>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div class="col-span-1 md:col-span-2">
                    <div class="flex items-center mb-4">
                        <i class="fas fa-users-cog text-2xl text-blue-400 mr-3"></i>
                        <span class="text-xl font-bold">Connectly CRM</span>
                    </div>
                    <p class="text-gray-400 mb-4">
                        Professional customer relationship management system designed for modern businesses. 
                        Streamline your sales process and grow your revenue.
                    </p>
                    <div class="flex space-x-4">
                        <a href="#" class="text-gray-400 hover:text-white transition duration-300">
                            <i class="fab fa-twitter text-xl"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-white transition duration-300">
                            <i class="fab fa-linkedin text-xl"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-white transition duration-300">
                            <i class="fab fa-github text-xl"></i>
                        </a>
                    </div>
                </div>
                <div>
                    <h3 class="text-lg font-semibold mb-4">Product</h3>
                    <ul class="space-y-2">
                        <li><a href="#features" class="text-gray-400 hover:text-white transition duration-300">Features</a></li>
                        <li><a href="#pricing" class="text-gray-400 hover:text-white transition duration-300">Pricing</a></li>
                        <li><a href="/api/docs" class="text-gray-400 hover:text-white transition duration-300">API Docs</a></li>
                        <li><a href="/api/info" class="text-gray-400 hover:text-white transition duration-300">API Info</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-lg font-semibold mb-4">Company</h3>
                    <ul class="space-y-2">
                        <li><a href="#" class="text-gray-400 hover:text-white transition duration-300">About</a></li>
                        <li><a href="#contact" class="text-gray-400 hover:text-white transition duration-300">Contact</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition duration-300">Privacy</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white transition duration-300">Terms</a></li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-gray-800 mt-8 pt-8 text-center">
                <p class="text-gray-400">
                    © {{ date('Y') }} Connectly CRM. Built with ❤️ for growing businesses.
                </p>
            </div>
        </div>
    </footer>

    <script>
        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    </script>
</body>
</html>

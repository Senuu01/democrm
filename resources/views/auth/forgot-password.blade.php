<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Forgot Password - Connectly CRM</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        .glass-card {
            backdrop-filter: blur(16px) saturate(180%);
            background-color: rgba(255, 255, 255, 0.95);
            border-radius: 12px;
            border: 1px solid rgba(209, 213, 219, 0.3);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }
        .input-focus {
            transition: all 0.3s ease;
        }
        .input-focus:focus {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(245, 87, 108, 0.2);
        }
        .btn-hover {
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        .btn-hover:before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.6s;
        }
        .btn-hover:hover:before {
            left: 100%;
        }
        .fade-in {
            animation: fadeInUp 0.6s ease forwards;
            opacity: 0;
            transform: translateY(30px);
        }
        .fade-in-delay-1 { animation-delay: 0.1s; }
        .fade-in-delay-2 { animation-delay: 0.2s; }
        .fade-in-delay-3 { animation-delay: 0.3s; }
        .fade-in-delay-4 { animation-delay: 0.4s; }
        
        @keyframes fadeInUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .floating-elements {
            position: absolute;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 0;
        }
        .floating-elements::before {
            content: '';
            position: absolute;
            width: 300px;
            height: 300px;
            background: linear-gradient(45deg, rgba(240, 147, 251, 0.1), rgba(245, 87, 108, 0.1));
            border-radius: 50%;
            top: -150px;
            right: -150px;
            animation: float 6s ease-in-out infinite;
        }
        .floating-elements::after {
            content: '';
            position: absolute;
            width: 200px;
            height: 200px;
            background: linear-gradient(45deg, rgba(236, 72, 153, 0.1), rgba(239, 68, 68, 0.1));
            border-radius: 50%;
            bottom: -100px;
            left: -100px;
            animation: float 8s ease-in-out infinite reverse;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(10deg); }
        }
        
        .pulse-ring {
            animation: pulse-ring 2s cubic-bezier(0.455, 0.03, 0.515, 0.955) infinite;
        }
        
        @keyframes pulse-ring {
            0% {
                transform: scale(0.8);
                opacity: 1;
            }
            80%, 100% {
                transform: scale(1.2);
                opacity: 0;
            }
        }
    </style>
</head>
<body class="gradient-bg min-h-screen flex items-center justify-center p-4 relative">
    <div class="floating-elements"></div>
    
    <div class="w-full max-w-md relative z-10">
        <div class="glass-card p-8 shadow-2xl">
            <!-- Header -->
            <div class="text-center mb-8 fade-in">
                <div class="mb-4 relative">
                    <div class="w-16 h-16 bg-gradient-to-r from-pink-500 to-rose-500 rounded-full mx-auto flex items-center justify-center shadow-lg relative">
                        <div class="absolute inset-0 rounded-full bg-pink-400 pulse-ring"></div>
                        <i class="fas fa-key text-white text-2xl relative z-10"></i>
                    </div>
                </div>
                <h1 class="text-3xl font-bold bg-gradient-to-r from-pink-600 to-rose-600 bg-clip-text text-transparent mb-2">
                    Forgot Password?
                </h1>
                <p class="text-gray-600">No worries, we'll send you a reset code</p>
            </div>

            <!-- Success/Error Messages -->
            @if(session('success'))
                <div class="bg-emerald-50 border-l-4 border-emerald-400 p-4 mb-6 rounded fade-in fade-in-delay-1">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle text-emerald-400 mr-3"></i>
                        <div>
                            <p class="text-emerald-700 font-semibold">Reset code sent!</p>
                            <p class="text-emerald-600 text-sm">{{ session('success') }}</p>
                        </div>
                    </div>
                </div>
            @endif

            @if($errors->any())
                <div class="bg-red-50 border-l-4 border-red-400 p-4 mb-6 rounded fade-in fade-in-delay-1">
                    <div class="flex items-start">
                        <i class="fas fa-exclamation-triangle text-red-400 mr-3 mt-0.5"></i>
                        <div>
                            @foreach($errors->all() as $error)
                                <p class="text-red-700 text-sm">{{ $error }}</p>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            <!-- Reset Form -->
            <form method="POST" action="{{ route('auth.send-reset-code') }}" class="space-y-6">
                @csrf
                
                <div class="fade-in fade-in-delay-2">
                    <label for="email" class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-envelope text-pink-500 mr-2"></i>Email Address
                    </label>
                    <input type="email" name="email" id="email" required
                           class="input-focus w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-pink-500 focus:outline-none bg-white/50"
                           placeholder="Enter your registered email"
                           value="{{ old('email') }}">
                    <p class="mt-2 text-sm text-gray-500 flex items-center">
                        <i class="fas fa-info-circle text-pink-400 mr-2"></i>
                        We'll send a 6-digit reset code to this email
                    </p>
                </div>

                <button type="submit" 
                        class="btn-hover w-full bg-gradient-to-r from-pink-500 to-rose-500 text-white py-3 px-6 rounded-xl font-semibold text-lg shadow-lg hover:shadow-xl transform hover:-translate-y-1 transition-all duration-300 fade-in fade-in-delay-3">
                    <i class="fas fa-paper-plane mr-2"></i>Send Reset Code
                </button>
            </form>

            <!-- Additional Help -->
            <div class="mt-8 fade-in fade-in-delay-4">
                <div class="bg-blue-50 border-l-4 border-blue-400 p-4 rounded">
                    <div class="flex items-start">
                        <i class="fas fa-lightbulb text-blue-400 mr-3 mt-0.5"></i>
                        <div>
                            <p class="text-blue-700 font-semibold text-sm">Reset Instructions</p>
                            <ul class="text-blue-600 text-sm mt-1 space-y-1">
                                <li>• Check your email inbox (and spam folder)</li>
                                <li>• Enter the 6-digit code on the next page</li>
                                <li>• Create a new secure password</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Divider -->
            <div class="relative my-8 fade-in fade-in-delay-4">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-gray-200"></div>
                </div>
                <div class="relative flex justify-center text-sm">
                    <span class="px-4 bg-white text-gray-500">or</span>
                </div>
            </div>

            <!-- Back to Login -->
            <div class="text-center fade-in fade-in-delay-4">
                <p class="text-gray-600">
                    Remember your password? 
                    <a href="{{ route('login') }}" 
                       class="font-semibold text-pink-600 hover:text-pink-500 transition-colors">
                        <i class="fas fa-arrow-left mr-1"></i>Back to sign in
                    </a>
                </p>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center mt-6 fade-in fade-in-delay-4">
            <p class="text-white/70 text-sm">
                <i class="fas fa-lock mr-1"></i>
                Secure password recovery process
            </p>
        </div>
    </div>

    <script>
        // Add loading state to form submission
        document.querySelector('form').addEventListener('submit', function(e) {
            const button = document.querySelector('button[type="submit"]');
            button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Sending code...';
            button.disabled = true;
        });

        // Enhanced input interactions
        document.querySelectorAll('input').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'scale(1.02)';
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.style.transform = 'scale(1)';
            });
        });

        // Email validation feedback
        document.getElementById('email').addEventListener('input', function() {
            const email = this.value;
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            
            if (email && !emailRegex.test(email)) {
                this.style.borderColor = '#ef4444';
            } else if (email) {
                this.style.borderColor = '#10b981';
            } else {
                this.style.borderColor = '#e5e7eb';
            }
        });
    </script>
</body>
</html>
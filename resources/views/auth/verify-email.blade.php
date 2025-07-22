<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verify Email - Connectly CRM</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
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
            box-shadow: 0 10px 25px rgba(79, 172, 254, 0.2);
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
            background: linear-gradient(45deg, rgba(79, 172, 254, 0.1), rgba(0, 242, 254, 0.1));
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
            background: linear-gradient(45deg, rgba(59, 130, 246, 0.1), rgba(34, 197, 94, 0.1));
            border-radius: 50%;
            bottom: -100px;
            left: -100px;
            animation: float 8s ease-in-out infinite reverse;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(10deg); }
        }
        
        .code-input {
            font-size: 1.5rem;
            letter-spacing: 0.5rem;
            text-align: center;
        }
        
        .email-icon {
            animation: bounce 2s infinite;
        }
        
        @keyframes bounce {
            0%, 20%, 53%, 80%, 100% {
                transform: translate3d(0,0,0);
            }
            40%, 43% {
                transform: translate3d(0, -10px, 0);
            }
            70% {
                transform: translate3d(0, -5px, 0);
            }
            90% {
                transform: translate3d(0, -2px, 0);
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
                <div class="mb-4">
                    <div class="w-16 h-16 bg-gradient-to-r from-blue-500 to-cyan-500 rounded-full mx-auto flex items-center justify-center shadow-lg">
                        <i class="fas fa-envelope-open-text text-white text-2xl email-icon"></i>
                    </div>
                </div>
                <h1 class="text-3xl font-bold bg-gradient-to-r from-blue-600 to-cyan-600 bg-clip-text text-transparent mb-2">
                    Check Your Email
                </h1>
                <p class="text-gray-600">
                    We've sent a verification code to
                    <br><strong class="text-blue-600">{{ session('email') }}</strong>
                </p>
            </div>

            <!-- Success/Error Messages -->
            @if(session('success'))
                <div class="bg-emerald-50 border-l-4 border-emerald-400 p-4 mb-6 rounded fade-in fade-in-delay-1">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle text-emerald-400 mr-3"></i>
                        <p class="text-emerald-700">{{ session('success') }}</p>
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

            <!-- Verification Form -->
            <form method="POST" action="{{ route('auth.verify-email.post') }}" class="space-y-6">
                @csrf
                
                <div class="fade-in fade-in-delay-2">
                    <label for="verification_code" class="block text-sm font-semibold text-gray-700 mb-4 text-center">
                        <i class="fas fa-hashtag text-blue-500 mr-2"></i>Enter 6-Digit Verification Code
                    </label>
                    <input type="text" name="verification_code" id="verification_code" required maxlength="6" 
                           class="code-input input-focus w-full px-4 py-4 border-2 border-gray-200 rounded-xl focus:border-blue-500 focus:outline-none bg-white/70 text-center"
                           placeholder="000000"
                           pattern="[0-9]{6}"
                           autocomplete="off">
                    <p class="mt-2 text-sm text-gray-500 text-center">
                        <i class="fas fa-clock text-blue-400 mr-1"></i>
                        Code expires in 60 minutes
                    </p>
                </div>

                <button type="submit" 
                        class="btn-hover w-full bg-gradient-to-r from-blue-500 to-cyan-500 text-white py-3 px-6 rounded-xl font-semibold text-lg shadow-lg hover:shadow-xl transform hover:-translate-y-1 transition-all duration-300 fade-in fade-in-delay-3">
                    <i class="fas fa-shield-check mr-2"></i>Verify Email
                </button>
            </form>

            <!-- Resend Code -->
            <div class="mt-8 fade-in fade-in-delay-4">
                <div class="bg-amber-50 border-l-4 border-amber-400 p-4 rounded">
                    <div class="flex items-start">
                        <i class="fas fa-question-circle text-amber-400 mr-3 mt-0.5"></i>
                        <div class="flex-1">
                            <p class="text-amber-700 font-semibold text-sm">Didn't receive the code?</p>
                            <ul class="text-amber-600 text-sm mt-1 space-y-1 mb-3">
                                <li>• Check your spam/junk folder</li>
                                <li>• Make sure the email address is correct</li>
                                <li>• Wait a few minutes and try again</li>
                            </ul>
                            
                            <!-- Resend Button -->
                            <form method="POST" action="{{ route('auth.resend-verification') }}" class="inline">
                                @csrf
                                <button type="submit" 
                                        class="bg-amber-500 hover:bg-amber-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors duration-200">
                                    <i class="fas fa-paper-plane mr-1"></i>Resend Code
                                </button>
                            </form>
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

            <!-- Back to Registration -->
            <div class="text-center fade-in fade-in-delay-4">
                <p class="text-gray-600">
                    Need to use a different email? 
                    <a href="{{ route('auth.email-register') }}" 
                       class="font-semibold text-blue-600 hover:text-blue-500 transition-colors">
                        <i class="fas fa-arrow-left mr-1"></i>Back to registration
                    </a>
                </p>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center mt-6 fade-in fade-in-delay-4">
            <p class="text-white/70 text-sm">
                <i class="fas fa-envelope mr-1"></i>
                Check your email and verify your account
            </p>
        </div>
    </div>

    <script>
        // Auto-format verification code input
        document.getElementById('verification_code').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, ''); // Remove non-digits
            if (value.length > 6) value = value.substring(0, 6);
            e.target.value = value;
            
            // Auto-submit when 6 digits are entered
            if (value.length === 6) {
                setTimeout(() => {
                    document.querySelector('form').submit();
                }, 500);
            }
        });

        // Add loading state to form submission
        document.querySelector('form').addEventListener('submit', function(e) {
            const button = document.querySelector('button[type="submit"]');
            button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Verifying...';
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

        // Focus on verification code input when page loads
        window.addEventListener('load', function() {
            document.getElementById('verification_code').focus();
        });
    </script>
</body>
</html>
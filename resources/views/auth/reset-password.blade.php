<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reset Password - Connectly CRM</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
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
            box-shadow: 0 10px 25px rgba(168, 237, 234, 0.3);
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
        .fade-in-delay-5 { animation-delay: 0.5s; }
        
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
            background: linear-gradient(45deg, rgba(168, 237, 234, 0.1), rgba(254, 214, 227, 0.1));
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
            background: linear-gradient(45deg, rgba(129, 230, 217, 0.1), rgba(116, 235, 213, 0.1));
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
            font-size: 1.25rem;
            letter-spacing: 0.3rem;
            text-align: center;
        }
        
        .password-strength {
            height: 4px;
            border-radius: 2px;
            margin-top: 8px;
            transition: all 0.3s ease;
        }
        .strength-weak { background: linear-gradient(to right, #ef4444 0%, #ef4444 33%, #e5e7eb 33%); }
        .strength-medium { background: linear-gradient(to right, #f59e0b 0%, #f59e0b 66%, #e5e7eb 66%); }
        .strength-strong { background: linear-gradient(to right, #10b981 0%, #10b981 100%); }
        
        .lock-icon {
            animation: shake 0.8s ease-in-out infinite alternate;
        }
        
        @keyframes shake {
            0% { transform: rotate(-5deg); }
            100% { transform: rotate(5deg); }
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
                    <div class="w-16 h-16 bg-gradient-to-r from-teal-500 to-green-500 rounded-full mx-auto flex items-center justify-center shadow-lg">
                        <i class="fas fa-unlock-alt text-white text-2xl lock-icon"></i>
                    </div>
                </div>
                <h1 class="text-3xl font-bold bg-gradient-to-r from-teal-600 to-green-600 bg-clip-text text-transparent mb-2">
                    Reset Password
                </h1>
                <p class="text-gray-600">
                    Enter the code and create a new secure password
                    @if(session('email'))
                        <br><span class="text-teal-600 font-medium">{{ session('email') }}</span>
                    @endif
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

            <!-- Reset Form -->
            <form method="POST" action="{{ route('auth.reset-password.post') }}" class="space-y-5">
                @csrf
                
                <div class="fade-in fade-in-delay-2">
                    <label for="reset_code" class="block text-sm font-semibold text-gray-700 mb-2 text-center">
                        <i class="fas fa-hashtag text-teal-500 mr-2"></i>6-Digit Reset Code
                    </label>
                    <input type="text" name="reset_code" id="reset_code" required maxlength="6"
                           class="code-input input-focus w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-teal-500 focus:outline-none bg-white/70"
                           placeholder="000000"
                           pattern="[0-9]{6}"
                           autocomplete="off">
                    <p class="mt-2 text-sm text-gray-500 text-center">
                        <i class="fas fa-clock text-teal-400 mr-1"></i>
                        Code from your email
                    </p>
                </div>

                <div class="fade-in fade-in-delay-3">
                    <label for="password" class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-key text-teal-500 mr-2"></i>New Password
                    </label>
                    <div class="relative">
                        <input type="password" name="password" id="password" required
                               class="input-focus w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-teal-500 focus:outline-none bg-white/50 pr-12"
                               placeholder="Create a strong password"
                               oninput="checkPasswordStrength()">
                        <button type="button" onclick="togglePassword('password', 'toggleIcon1')" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-teal-500 transition-colors">
                            <i class="fas fa-eye" id="toggleIcon1"></i>
                        </button>
                    </div>
                    <div class="password-strength bg-gray-200" id="passwordStrength"></div>
                    <p class="text-xs text-gray-500 mt-1" id="strengthText">Password must be at least 6 characters</p>
                </div>

                <div class="fade-in fade-in-delay-4">
                    <label for="password_confirmation" class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-check-double text-teal-500 mr-2"></i>Confirm New Password
                    </label>
                    <div class="relative">
                        <input type="password" name="password_confirmation" id="password_confirmation" required
                               class="input-focus w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:border-teal-500 focus:outline-none bg-white/50 pr-12"
                               placeholder="Confirm your new password"
                               oninput="checkPasswordMatch()">
                        <button type="button" onclick="togglePassword('password_confirmation', 'toggleIcon2')" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-teal-500 transition-colors">
                            <i class="fas fa-eye" id="toggleIcon2"></i>
                        </button>
                    </div>
                    <p class="text-xs mt-1 hidden" id="passwordMatch">
                        <i class="fas fa-times text-red-500 mr-1"></i>
                        <span class="text-red-500">Passwords don't match</span>
                    </p>
                </div>

                <button type="submit" 
                        class="btn-hover w-full bg-gradient-to-r from-teal-500 to-green-500 text-white py-3 px-6 rounded-xl font-semibold text-lg shadow-lg hover:shadow-xl transform hover:-translate-y-1 transition-all duration-300 fade-in fade-in-delay-5">
                    <i class="fas fa-shield-alt mr-2"></i>Reset Password
                </button>
            </form>

            <!-- Security Tips -->
            <div class="mt-8 fade-in fade-in-delay-5">
                <div class="bg-blue-50 border-l-4 border-blue-400 p-4 rounded">
                    <div class="flex items-start">
                        <i class="fas fa-lightbulb text-blue-400 mr-3 mt-0.5"></i>
                        <div>
                            <p class="text-blue-700 font-semibold text-sm">Password Tips</p>
                            <ul class="text-blue-600 text-sm mt-1 space-y-1">
                                <li>• Use 8+ characters with mixed case</li>
                                <li>• Include numbers and symbols</li>
                                <li>• Avoid common words or patterns</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Divider -->
            <div class="relative my-8 fade-in fade-in-delay-5">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-gray-200"></div>
                </div>
                <div class="relative flex justify-center text-sm">
                    <span class="px-4 bg-white text-gray-500">or</span>
                </div>
            </div>

            <!-- Back to Login -->
            <div class="text-center fade-in fade-in-delay-5">
                <p class="text-gray-600">
                    Remembered your password? 
                    <a href="{{ route('login') }}" 
                       class="font-semibold text-teal-600 hover:text-teal-500 transition-colors">
                        <i class="fas fa-arrow-left mr-1"></i>Back to sign in
                    </a>
                </p>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center mt-6 fade-in fade-in-delay-5">
            <p class="text-white/70 text-sm">
                <i class="fas fa-shield-alt mr-1"></i>
                Secure password reset process
            </p>
        </div>
    </div>

    <script>
        // Auto-format reset code input
        document.getElementById('reset_code').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, ''); // Remove non-digits
            if (value.length > 6) value = value.substring(0, 6);
            e.target.value = value;
        });

        function togglePassword(inputId, iconId) {
            const passwordInput = document.getElementById(inputId);
            const toggleIcon = document.getElementById(iconId);
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.className = 'fas fa-eye-slash';
            } else {
                passwordInput.type = 'password';
                toggleIcon.className = 'fas fa-eye';
            }
        }

        function checkPasswordStrength() {
            const password = document.getElementById('password').value;
            const strengthBar = document.getElementById('passwordStrength');
            const strengthText = document.getElementById('strengthText');

            let strength = 0;
            if (password.length >= 6) strength++;
            if (password.match(/[a-z]+/)) strength++;
            if (password.match(/[A-Z]+/)) strength++;
            if (password.match(/[0-9]+/)) strength++;
            if (password.match(/[^a-zA-Z0-9]+/)) strength++;

            if (strength < 3) {
                strengthBar.className = 'password-strength strength-weak';
                strengthText.textContent = 'Weak password';
                strengthText.className = 'text-xs text-red-500 mt-1';
            } else if (strength < 4) {
                strengthBar.className = 'password-strength strength-medium';
                strengthText.textContent = 'Medium strength';
                strengthText.className = 'text-xs text-yellow-500 mt-1';
            } else {
                strengthBar.className = 'password-strength strength-strong';
                strengthText.textContent = 'Strong password';
                strengthText.className = 'text-xs text-green-500 mt-1';
            }
        }

        function checkPasswordMatch() {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('password_confirmation').value;
            const matchMessage = document.getElementById('passwordMatch');

            if (confirmPassword && password !== confirmPassword) {
                matchMessage.classList.remove('hidden');
                matchMessage.innerHTML = '<i class="fas fa-times text-red-500 mr-1"></i><span class="text-red-500">Passwords don\'t match</span>';
            } else if (confirmPassword && password === confirmPassword) {
                matchMessage.classList.remove('hidden');
                matchMessage.innerHTML = '<i class="fas fa-check text-green-500 mr-1"></i><span class="text-green-500">Passwords match</span>';
            } else {
                matchMessage.classList.add('hidden');
            }
        }

        // Add loading state to form submission
        document.querySelector('form').addEventListener('submit', function(e) {
            const button = document.querySelector('button[type="submit"]');
            button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Resetting...';
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

        // Focus on reset code input when page loads
        window.addEventListener('load', function() {
            document.getElementById('reset_code').focus();
        });
    </script>
</body>
</html>
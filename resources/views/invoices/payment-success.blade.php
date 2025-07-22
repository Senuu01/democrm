<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Successful - Connectly CRM</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex items-center justify-center">
        <div class="max-w-md w-full mx-4">
            <!-- Success Card -->
            <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                <!-- Success Icon -->
                <div class="bg-green-500 p-6">
                    <div class="text-center">
                        <div class="mx-auto w-16 h-16 bg-white rounded-full flex items-center justify-center mb-4">
                            <i class="fas fa-check text-2xl text-green-500"></i>
                        </div>
                        <h1 class="text-xl font-bold text-white">Payment Successful!</h1>
                        <p class="text-green-100 mt-2">Thank you for your payment</p>
                    </div>
                </div>

                <!-- Payment Details -->
                <div class="p-6">
                    <div class="text-center mb-6">
                        <div class="text-3xl font-bold text-gray-900 mb-2">
                            ${{ number_format($amount, 2) }}
                        </div>
                        <p class="text-gray-600">has been successfully processed</p>
                    </div>

                    <!-- Payment Information -->
                    <div class="border-t border-gray-200 pt-6">
                        <div class="space-y-3">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Invoice ID:</span>
                                <span class="font-medium text-gray-900">#{{ $invoice_id }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Payment Date:</span>
                                <span class="font-medium text-gray-900">{{ date('M j, Y g:i A') }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Payment Method:</span>
                                <span class="font-medium text-gray-900">
                                    <i class="fas fa-credit-card mr-1"></i>
                                    Card ending in •••• {{ substr($session->payment_intent ?? '0000', -4) }}
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Status:</span>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    <i class="fas fa-check-circle mr-1"></i>
                                    Paid
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- What's Next -->
                    <div class="border-t border-gray-200 pt-6 mt-6">
                        <h3 class="text-sm font-medium text-gray-900 mb-3">What happens next?</h3>
                        <div class="space-y-2 text-sm text-gray-600">
                            <div class="flex items-start">
                                <i class="fas fa-envelope text-green-500 mt-0.5 mr-2 flex-shrink-0"></i>
                                <span>A payment confirmation email has been sent to your email address</span>
                            </div>
                            <div class="flex items-start">
                                <i class="fas fa-file-pdf text-red-500 mt-0.5 mr-2 flex-shrink-0"></i>
                                <span>Your receipt and invoice are available for download</span>
                            </div>
                            <div class="flex items-start">
                                <i class="fas fa-headset text-blue-500 mt-0.5 mr-2 flex-shrink-0"></i>
                                <span>Our team will contact you if any follow-up is needed</span>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="mt-8 space-y-3">
                        <a href="{{ route('invoices.show', $invoice_id) }}" 
                           class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700">
                            <i class="fas fa-file-invoice mr-2"></i>
                            View Invoice
                        </a>
                        
                        <a href="{{ route('invoices.pdf', $invoice_id) }}" 
                           class="w-full inline-flex justify-center items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                            <i class="fas fa-download mr-2"></i>
                            Download Receipt
                        </a>
                    </div>

                    <!-- Contact Support -->
                    <div class="mt-6 text-center">
                        <p class="text-xs text-gray-500">
                            Questions about your payment? 
                            <a href="mailto:support@connectly.com" class="text-blue-600 hover:text-blue-500">
                                Contact Support
                            </a>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Additional Information -->
            <div class="mt-6 text-center">
                <div class="bg-white rounded-lg shadow p-4">
                    <div class="flex items-center justify-center space-x-4 text-sm text-gray-600">
                        <div class="flex items-center">
                            <i class="fas fa-shield-alt text-green-500 mr-1"></i>
                            <span>Secure Payment</span>
                        </div>
                        <div class="flex items-center">
                            <i class="fas fa-lock text-blue-500 mr-1"></i>
                            <span>SSL Encrypted</span>
                        </div>
                        <div class="flex items-center">
                            <i class="fab fa-stripe text-purple-500 mr-1"></i>
                            <span>Powered by Stripe</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Back to Dashboard -->
            <div class="mt-4 text-center">
                <a href="{{ route('dashboard') }}" class="text-blue-600 hover:text-blue-500 text-sm font-medium">
                    <i class="fas fa-arrow-left mr-1"></i>
                    Back to Dashboard
                </a>
            </div>
        </div>
    </div>

    <!-- Confetti Animation -->
    <script>
        // Simple confetti effect
        function createConfetti() {
            const colors = ['#10b981', '#3b82f6', '#8b5cf6', '#f59e0b', '#ef4444'];
            const confettiCount = 50;
            
            for (let i = 0; i < confettiCount; i++) {
                const confetti = document.createElement('div');
                confetti.style.position = 'fixed';
                confetti.style.width = '10px';
                confetti.style.height = '10px';
                confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
                confetti.style.left = Math.random() * 100 + '%';
                confetti.style.top = '-10px';
                confetti.style.zIndex = '1000';
                confetti.style.borderRadius = '50%';
                confetti.style.pointerEvents = 'none';
                
                document.body.appendChild(confetti);
                
                // Animate confetti falling
                const fall = confetti.animate([
                    { transform: 'translateY(-10px) rotate(0deg)', opacity: 1 },
                    { transform: `translateY(${window.innerHeight + 10}px) rotate(360deg)`, opacity: 0 }
                ], {
                    duration: Math.random() * 3000 + 2000,
                    easing: 'cubic-bezier(0.5, 0, 0.5, 1)'
                });
                
                fall.addEventListener('finish', () => {
                    confetti.remove();
                });
            }
        }
        
        // Trigger confetti on page load
        window.addEventListener('load', () => {
            setTimeout(createConfetti, 500);
        });
    </script>
</body>
</html> 
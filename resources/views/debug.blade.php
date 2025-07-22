<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Tools - Connectly CRM</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen py-8">
        <div class="max-w-4xl mx-auto px-4">
            <h1 class="text-3xl font-bold text-gray-900 mb-8">Debug Tools - Connectly CRM</h1>
            
            <!-- Email Testing -->
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <h2 class="text-xl font-semibold mb-4">📧 Email Testing</h2>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Test Email Address:</label>
                        <input type="email" id="testEmail" class="border border-gray-300 rounded-md px-3 py-2 w-full max-w-md" 
                               placeholder="your-email@example.com">
                    </div>
                    <button onclick="testEmail()" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md">
                        Send Test Email
                    </button>
                    <div id="emailResult" class="mt-4"></div>
                </div>
            </div>

            <!-- Database Operations -->
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <h2 class="text-xl font-semibold mb-4">🗄️ Database Operations</h2>
                <div class="space-y-4">
                    <!-- Create Test User -->
                    <div class="border-b pb-4">
                        <h3 class="font-medium mb-2">Create Test User</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Email:</label>
                                <input type="email" id="userEmail" class="border border-gray-300 rounded-md px-3 py-2 w-full" 
                                       value="test@example.com">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Name:</label>
                                <input type="text" id="userName" class="border border-gray-300 rounded-md px-3 py-2 w-full" 
                                       value="Test User">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Password:</label>
                                <input type="text" id="userPassword" class="border border-gray-300 rounded-md px-3 py-2 w-full" 
                                       value="password123">
                            </div>
                        </div>
                        <button onclick="createTestUser()" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-md">
                            Create Test User
                        </button>
                        <div id="createUserResult" class="mt-4"></div>
                    </div>

                    <!-- Clear Database -->
                    <div>
                        <h3 class="font-medium mb-2">Clear All Users</h3>
                        <p class="text-sm text-gray-600 mb-4">⚠️ This will delete ALL users from the database. Use with caution!</p>
                        <button onclick="clearAllUsers()" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-md">
                            Clear All Users
                        </button>
                        <div id="clearUsersResult" class="mt-4"></div>
                    </div>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-semibold mb-4">🔗 Quick Links</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <a href="{{ route('login') }}" class="bg-blue-100 hover:bg-blue-200 p-4 rounded-lg text-center">
                        <div class="font-medium">Login Page</div>
                        <div class="text-sm text-gray-600">Test login functionality</div>
                    </a>
                    <a href="{{ route('auth.email-register') }}" class="bg-green-100 hover:bg-green-200 p-4 rounded-lg text-center">
                        <div class="font-medium">Register Page</div>
                        <div class="text-sm text-gray-600">Test user registration</div>
                    </a>
                    <a href="{{ route('auth.forgot-password') }}" class="bg-yellow-100 hover:bg-yellow-200 p-4 rounded-lg text-center">
                        <div class="font-medium">Password Reset</div>
                        <div class="text-sm text-gray-600">Test password reset</div>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        function showResult(elementId, data, isError = false) {
            const element = document.getElementById(elementId);
            const bgColor = isError ? 'bg-red-100 border-red-400 text-red-700' : 'bg-green-100 border-green-400 text-green-700';
            element.innerHTML = `<div class="border ${bgColor} px-4 py-3 rounded">${JSON.stringify(data, null, 2)}</div>`;
        }

        function testEmail() {
            const email = document.getElementById('testEmail').value;
            if (!email) {
                showResult('emailResult', {error: 'Please enter an email address'}, true);
                return;
            }

            fetch(`/test-email?test_email=${encodeURIComponent(email)}`)
                .then(response => response.json())
                .then(data => {
                    showResult('emailResult', data, !!data.error);
                })
                .catch(error => {
                    showResult('emailResult', {error: error.message}, true);
                });
        }

        function createTestUser() {
            const email = document.getElementById('userEmail').value;
            const name = document.getElementById('userName').value;
            const password = document.getElementById('userPassword').value;

            fetch(`/create-test-user?email=${encodeURIComponent(email)}&name=${encodeURIComponent(name)}&password=${encodeURIComponent(password)}`)
                .then(response => response.json())
                .then(data => {
                    showResult('createUserResult', data, !!data.error);
                })
                .catch(error => {
                    showResult('createUserResult', {error: error.message}, true);
                });
        }

        function clearAllUsers() {
            if (!confirm('Are you sure you want to delete ALL users? This cannot be undone!')) {
                return;
            }

            fetch('/clear-all-users')
                .then(response => response.json())
                .then(data => {
                    showResult('clearUsersResult', data, !!data.error);
                })
                .catch(error => {
                    showResult('clearUsersResult', {error: error.message}, true);
                });
        }
    </script>
</body>
</html> 
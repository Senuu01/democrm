<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connectly CRM API Documentation</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism-tomorrow.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-core.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/plugins/autoloader/prism-autoloader.min.js"></script>
    <style>
        .code-block {
            border-radius: 8px;
            overflow: hidden;
        }
        .method-get { @apply bg-green-100 text-green-800 border-green-200; }
        .method-post { @apply bg-blue-100 text-blue-800 border-blue-200; }
        .method-put { @apply bg-yellow-100 text-yellow-800 border-yellow-200; }
        .method-patch { @apply bg-purple-100 text-purple-800 border-purple-200; }
        .method-delete { @apply bg-red-100 text-red-800 border-red-200; }
    </style>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen">
        <!-- Header -->
        <header class="bg-white shadow">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <div class="flex items-center">
                        <a href="{{ route('dashboard') }}" class="text-xl font-semibold text-gray-900 hover:text-blue-600">
                            <i class="fas fa-arrow-left mr-2"></i>Connectly CRM
                        </a>
                        <span class="ml-4 text-gray-500">API Documentation</span>
                    </div>
                    <div class="flex items-center space-x-4">
                        <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm font-medium">v1.0.0</span>
                        <a href="/api/info" target="_blank" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">
                            <i class="fas fa-external-link-alt mr-1"></i>API Info
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                <!-- Sidebar Navigation -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-lg shadow p-6 sticky top-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">
                            <i class="fas fa-list text-blue-500 mr-2"></i>Contents
                        </h3>
                        <nav class="space-y-2">
                            <a href="#overview" class="block text-sm text-gray-600 hover:text-blue-600">Overview</a>
                            <a href="#authentication" class="block text-sm text-gray-600 hover:text-blue-600">Authentication</a>
                            <a href="#customers" class="block text-sm text-gray-600 hover:text-blue-600">Customers</a>
                            <a href="#errors" class="block text-sm text-gray-600 hover:text-blue-600">Error Handling</a>
                            <a href="#examples" class="block text-sm text-gray-600 hover:text-blue-600">Examples</a>
                        </nav>
                    </div>
                </div>

                <!-- Main Content -->
                <div class="lg:col-span-3 space-y-8">
                    <!-- Overview Section -->
                    <section id="overview" class="bg-white rounded-lg shadow p-6">
                        <h2 class="text-2xl font-bold text-gray-900 mb-4">
                            <i class="fas fa-info-circle text-blue-500 mr-3"></i>API Overview
                        </h2>
                        <div class="prose max-w-none">
                            <p class="text-gray-700 mb-4">
                                The Connectly CRM API provides programmatic access to your customer relationship management data. 
                                Built with REST principles, it uses standard HTTP methods and returns JSON responses.
                            </p>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-6">
                                <div class="bg-blue-50 p-4 rounded-lg">
                                    <h4 class="font-semibold text-blue-900 mb-2">
                                        <i class="fas fa-server mr-2"></i>Base URL
                                    </h4>
                                    <code class="text-sm bg-white px-2 py-1 rounded">{{ url('/api') }}</code>
                                </div>
                                <div class="bg-green-50 p-4 rounded-lg">
                                    <h4 class="font-semibold text-green-900 mb-2">
                                        <i class="fas fa-shield-alt mr-2"></i>Authentication
                                    </h4>
                                    <p class="text-sm text-green-700">Bearer Token Required</p>
                                </div>
                                <div class="bg-purple-50 p-4 rounded-lg">
                                    <h4 class="font-semibold text-purple-900 mb-2">
                                        <i class="fas fa-clock mr-2"></i>Rate Limit
                                    </h4>
                                    <p class="text-sm text-purple-700">1000 requests/hour</p>
                                </div>
                                <div class="bg-yellow-50 p-4 rounded-lg">
                                    <h4 class="font-semibold text-yellow-900 mb-2">
                                        <i class="fas fa-code mr-2"></i>Response Format
                                    </h4>
                                    <p class="text-sm text-yellow-700">JSON with standard structure</p>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Authentication Section -->
                    <section id="authentication" class="bg-white rounded-lg shadow p-6">
                        <h2 class="text-2xl font-bold text-gray-900 mb-4">
                            <i class="fas fa-key text-green-500 mr-3"></i>Authentication
                        </h2>
                        
                        <div class="space-y-6">
                            <!-- Login -->
                            <div class="border-l-4 border-blue-500 pl-4">
                                <div class="flex items-center mb-2">
                                    <span class="method-post px-3 py-1 rounded text-sm font-medium border mr-3">POST</span>
                                    <code class="text-sm font-mono">/api/auth/login</code>
                                </div>
                                <p class="text-gray-600 text-sm mb-3">Authenticate and receive access token</p>
                                
                                <div class="bg-gray-50 rounded-lg p-4 mb-3">
                                    <h4 class="font-medium text-gray-900 mb-2">Request Body:</h4>
                                    <pre class="code-block"><code class="language-json">{
  "email": "user@example.com",
  "password": "your-password"
}</code></pre>
                                </div>
                                
                                <div class="bg-gray-50 rounded-lg p-4">
                                    <h4 class="font-medium text-gray-900 mb-2">Response:</h4>
                                    <pre class="code-block"><code class="language-json">{
  "success": true,
  "message": "Login successful",
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "user@example.com"
    },
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
    "expires_at": "2024-02-15T10:30:00Z"
  }
}</code></pre>
                                </div>
                            </div>

                            <!-- Using Token -->
                            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                                <h4 class="font-medium text-yellow-900 mb-2">
                                    <i class="fas fa-lightbulb mr-2"></i>Using Your Token
                                </h4>
                                <p class="text-yellow-800 text-sm mb-2">Include the token in all authenticated requests:</p>
                                <pre class="code-block"><code class="language-bash">Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...</code></pre>
                            </div>

                            <!-- Other Auth Endpoints -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div class="border rounded-lg p-3">
                                    <div class="flex items-center mb-2">
                                        <span class="method-post px-2 py-1 rounded text-xs font-medium border mr-2">POST</span>
                                        <code class="text-xs">/auth/refresh</code>
                                    </div>
                                    <p class="text-xs text-gray-600">Refresh token</p>
                                </div>
                                <div class="border rounded-lg p-3">
                                    <div class="flex items-center mb-2">
                                        <span class="method-get px-2 py-1 rounded text-xs font-medium border mr-2">GET</span>
                                        <code class="text-xs">/auth/me</code>
                                    </div>
                                    <p class="text-xs text-gray-600">Get user info</p>
                                </div>
                                <div class="border rounded-lg p-3">
                                    <div class="flex items-center mb-2">
                                        <span class="method-post px-2 py-1 rounded text-xs font-medium border mr-2">POST</span>
                                        <code class="text-xs">/auth/logout</code>
                                    </div>
                                    <p class="text-xs text-gray-600">Logout</p>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Customers Section -->
                    <section id="customers" class="bg-white rounded-lg shadow p-6">
                        <h2 class="text-2xl font-bold text-gray-900 mb-4">
                            <i class="fas fa-users text-purple-500 mr-3"></i>Customer Management
                        </h2>
                        
                        <div class="space-y-6">
                            <!-- Get Customers -->
                            <div class="border-l-4 border-green-500 pl-4">
                                <div class="flex items-center mb-2">
                                    <span class="method-get px-3 py-1 rounded text-sm font-medium border mr-3">GET</span>
                                    <code class="text-sm font-mono">/api/customers</code>
                                </div>
                                <p class="text-gray-600 text-sm mb-3">Get paginated list of customers with search and filtering</p>
                                
                                <div class="bg-gray-50 rounded-lg p-4 mb-3">
                                    <h4 class="font-medium text-gray-900 mb-2">Query Parameters:</h4>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-sm">
                                        <div><code>limit</code> - Items per page (default: 10, max: 100)</div>
                                        <div><code>offset</code> - Items to skip (default: 0)</div>
                                        <div><code>search</code> - Search by name, email, or company</div>
                                        <div><code>status</code> - Filter by status (active/inactive/prospect)</div>
                                        <div><code>sort</code> - Sort field (default: created_at)</div>
                                        <div><code>order</code> - Sort order (asc/desc, default: desc)</div>
                                    </div>
                                </div>
                                
                                <div class="bg-gray-50 rounded-lg p-4">
                                    <h4 class="font-medium text-gray-900 mb-2">Example Response:</h4>
                                    <pre class="code-block"><code class="language-json">{
  "success": true,
  "message": "Customers retrieved successfully",
  "data": [
    {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com",
      "company": "Acme Corp",
      "status": "active",
      "created_at": "2024-01-15T10:30:00Z"
    }
  ],
  "meta": {
    "total": 150,
    "limit": 10,
    "offset": 0,
    "has_more": true,
    "current_page": 1,
    "total_pages": 15
  }
}</code></pre>
                                </div>
                            </div>

                            <!-- Create Customer -->
                            <div class="border-l-4 border-blue-500 pl-4">
                                <div class="flex items-center mb-2">
                                    <span class="method-post px-3 py-1 rounded text-sm font-medium border mr-3">POST</span>
                                    <code class="text-sm font-mono">/api/customers</code>
                                </div>
                                <p class="text-gray-600 text-sm mb-3">Create a new customer</p>
                                
                                <div class="bg-gray-50 rounded-lg p-4">
                                    <h4 class="font-medium text-gray-900 mb-2">Request Body:</h4>
                                    <pre class="code-block"><code class="language-json">{
  "name": "Jane Smith",
  "email": "jane@example.com",
  "phone": "+1234567890",
  "company": "Tech Solutions Inc",
  "address": "123 Main St, City, State 12345",
  "status": "active"
}</code></pre>
                                </div>
                            </div>

                            <!-- Other Customer Endpoints -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="border rounded-lg p-4">
                                    <div class="flex items-center mb-2">
                                        <span class="method-get px-2 py-1 rounded text-xs font-medium border mr-2">GET</span>
                                        <code class="text-xs">/customers/{id}</code>
                                    </div>
                                    <p class="text-xs text-gray-600 mb-2">Get specific customer</p>
                                    <p class="text-xs text-blue-600">?include=proposals,invoices</p>
                                </div>
                                <div class="border rounded-lg p-4">
                                    <div class="flex items-center mb-2">
                                        <span class="method-put px-2 py-1 rounded text-xs font-medium border mr-2">PUT</span>
                                        <code class="text-xs">/customers/{id}</code>
                                    </div>
                                    <p class="text-xs text-gray-600">Update customer (full)</p>
                                </div>
                                <div class="border rounded-lg p-4">
                                    <div class="flex items-center mb-2">
                                        <span class="method-patch px-2 py-1 rounded text-xs font-medium border mr-2">PATCH</span>
                                        <code class="text-xs">/customers/{id}</code>
                                    </div>
                                    <p class="text-xs text-gray-600">Update customer (partial)</p>
                                </div>
                                <div class="border rounded-lg p-4">
                                    <div class="flex items-center mb-2">
                                        <span class="method-delete px-2 py-1 rounded text-xs font-medium border mr-2">DELETE</span>
                                        <code class="text-xs">/customers/{id}</code>
                                    </div>
                                    <p class="text-xs text-gray-600">Soft delete customer</p>
                                </div>
                                <div class="border rounded-lg p-4">
                                    <div class="flex items-center mb-2">
                                        <span class="method-get px-2 py-1 rounded text-xs font-medium border mr-2">GET</span>
                                        <code class="text-xs">/customers/stats</code>
                                    </div>
                                    <p class="text-xs text-gray-600">Get customer statistics</p>
                                </div>
                                <div class="border rounded-lg p-4">
                                    <div class="flex items-center mb-2">
                                        <span class="method-post px-2 py-1 rounded text-xs font-medium border mr-2">POST</span>
                                        <code class="text-xs">/customers/bulk</code>
                                    </div>
                                    <p class="text-xs text-gray-600">Bulk operations</p>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Error Handling Section -->
                    <section id="errors" class="bg-white rounded-lg shadow p-6">
                        <h2 class="text-2xl font-bold text-gray-900 mb-4">
                            <i class="fas fa-exclamation-triangle text-red-500 mr-3"></i>Error Handling
                        </h2>
                        
                        <div class="space-y-4">
                            <p class="text-gray-700">The API uses standard HTTP status codes and returns detailed error information:</p>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                                    <h4 class="font-medium text-red-900 mb-2">4xx Client Errors</h4>
                                    <ul class="text-sm text-red-700 space-y-1">
                                        <li><code>400</code> - Bad Request</li>
                                        <li><code>401</code> - Unauthorized</li>
                                        <li><code>404</code> - Not Found</li>
                                        <li><code>409</code> - Conflict</li>
                                        <li><code>422</code> - Validation Error</li>
                                    </ul>
                                </div>
                                <div class="bg-orange-50 border border-orange-200 rounded-lg p-4">
                                    <h4 class="font-medium text-orange-900 mb-2">5xx Server Errors</h4>
                                    <ul class="text-sm text-orange-700 space-y-1">
                                        <li><code>500</code> - Internal Server Error</li>
                                        <li><code>503</code> - Service Unavailable</li>
                                    </ul>
                                </div>
                            </div>
                            
                            <div class="bg-gray-50 rounded-lg p-4">
                                <h4 class="font-medium text-gray-900 mb-2">Error Response Format:</h4>
                                <pre class="code-block"><code class="language-json">{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "email": ["The email field is required."],
    "name": ["The name field is required."]
  }
}</code></pre>
                            </div>
                        </div>
                    </section>

                    <!-- Examples Section -->
                    <section id="examples" class="bg-white rounded-lg shadow p-6">
                        <h2 class="text-2xl font-bold text-gray-900 mb-4">
                            <i class="fas fa-code text-indigo-500 mr-3"></i>Code Examples
                        </h2>
                        
                        <div class="space-y-6">
                            <!-- cURL Example -->
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 mb-3">
                                    <i class="fas fa-terminal text-green-600 mr-2"></i>cURL
                                </h3>
                                <div class="bg-gray-900 rounded-lg p-4">
                                    <pre class="text-green-400 text-sm"><code># Login
curl -X POST {{ url('/api/auth/login') }} \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"password"}'

# Get customers
curl -X GET {{ url('/api/customers') }}?limit=20&search=john \
  -H "Authorization: Bearer YOUR_TOKEN"

# Create customer
curl -X POST {{ url('/api/customers') }} \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "John Doe",
    "email": "john@example.com",
    "company": "Acme Corp",
    "status": "active"
  }'</code></pre>
                                </div>
                            </div>

                            <!-- JavaScript Example -->
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 mb-3">
                                    <i class="fab fa-js-square text-yellow-600 mr-2"></i>JavaScript (Fetch)
                                </h3>
                                <div class="bg-gray-900 rounded-lg p-4">
                                    <pre class="text-blue-400 text-sm"><code class="language-javascript">// Login
const login = async () => {
  const response = await fetch('{{ url('/api/auth/login') }}', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({
      email: 'user@example.com',
      password: 'password'
    })
  });
  
  const data = await response.json();
  localStorage.setItem('token', data.data.token);
  return data;
};

// Get customers
const getCustomers = async () => {
  const token = localStorage.getItem('token');
  const response = await fetch('{{ url('/api/customers') }}?limit=20', {
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json',
    }
  });
  
  return await response.json();
};</code></pre>
                                </div>
                            </div>

                            <!-- PHP Example -->
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 mb-3">
                                    <i class="fab fa-php text-purple-600 mr-2"></i>PHP
                                </h3>
                                <div class="bg-gray-900 rounded-lg p-4">
                                    <pre class="text-purple-400 text-sm"><code class="language-php"><?php
// Login
$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => '{{ url('/api/auth/login') }}',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_POSTFIELDS => json_encode([
        'email' => 'user@example.com',
        'password' => 'password'
    ])
]);

$response = curl_exec($curl);
$data = json_decode($response, true);
$token = $data['data']['token'];

// Get customers
curl_setopt_array($curl, [
    CURLOPT_URL => '{{ url('/api/customers') }}',
    CURLOPT_HTTPGET => true,
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer $token",
        'Content-Type: application/json'
    ]
]);

$customers = json_decode(curl_exec($curl), true);
curl_close($curl);
?></code></pre>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Footer -->
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="font-semibold text-blue-900 mb-1">Need Help?</h3>
                                <p class="text-sm text-blue-700">Contact our support team for assistance with the API.</p>
                            </div>
                            <div class="flex space-x-3">
                                <a href="/api/info" target="_blank" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm">
                                    <i class="fas fa-info-circle mr-1"></i>API Info
                                </a>
                                <a href="/api/health" target="_blank" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded text-sm">
                                    <i class="fas fa-heartbeat mr-1"></i>Health Check
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Smooth scrolling for navigation links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });
    </script>
</body>
</html> 
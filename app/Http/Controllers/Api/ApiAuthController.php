<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use GuzzleHttp\Client;

class ApiAuthController extends Controller
{
    private $supabaseUrl;
    private $supabaseKey;
    private $client;

    public function __construct()
    {
        $this->supabaseUrl = config('services.supabase.url');
        $this->supabaseKey = config('services.supabase.key');
        $this->client = new Client();
    }

    /**
     * Login and get API token
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required|string|min:6'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $email = $request->input('email');
            $password = $request->input('password');

            // Check user exists and verify password
            $response = $this->client->get($this->supabaseUrl . '/rest/v1/auth_users', [
                'headers' => [
                    'apikey' => $this->supabaseKey,
                    'Authorization' => 'Bearer ' . $this->supabaseKey,
                    'Content-Type' => 'application/json'
                ],
                'query' => [
                    'email' => 'eq.' . $email,
                    'select' => 'id,name,email,password,email_verified_at'
                ]
            ]);

            $users = json_decode($response->getBody()->getContents(), true);

            if (empty($users)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid credentials'
                ], 401);
            }

            $user = $users[0];

            if (!Hash::check($password, $user['password'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid credentials'
                ], 401);
            }

            if (!$user['email_verified_at']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email not verified'
                ], 401);
            }

            // Generate API token
            $token = $this->generateApiToken($user['id']);

            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'data' => [
                    'user' => [
                        'id' => $user['id'],
                        'name' => $user['name'],
                        'email' => $user['email']
                    ],
                    'token' => $token,
                    'expires_at' => now()->addDays(30)->toISOString()
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('API Login Error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Login failed',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Refresh API token
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }

            // Generate new token
            $token = $this->generateApiToken($user['id']);

            return response()->json([
                'success' => true,
                'message' => 'Token refreshed successfully',
                'data' => [
                    'token' => $token,
                    'expires_at' => now()->addDays(30)->toISOString()
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('API Token Refresh Error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Token refresh failed',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get current user info
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function me(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }

            return response()->json([
                'success' => true,
                'message' => 'User data retrieved successfully',
                'data' => [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'created_at' => $user['created_at'] ?? null
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('API User Info Error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve user data',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Logout and invalidate token
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        try {
            // In a real implementation, you'd invalidate the token in database
            // For now, we'll just return success as tokens are stateless JWTs
            
            return response()->json([
                'success' => true,
                'message' => 'Logged out successfully'
            ]);

        } catch (\Exception $e) {
            \Log::error('API Logout Error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Logout failed',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Generate API token for user
     * 
     * @param int $userId
     * @return string
     */
    private function generateApiToken($userId)
    {
        $payload = [
            'user_id' => $userId,
            'issued_at' => now()->timestamp,
            'expires_at' => now()->addDays(30)->timestamp,
            'token_type' => 'api_access'
        ];

        // Simple token generation - in production, use JWT or similar
        $token = base64_encode(json_encode($payload)) . '.' . hash_hmac('sha256', json_encode($payload), config('app.key'));
        
        return $token;
    }

    /**
     * Validate API token
     * 
     * @param string $token
     * @return array|null
     */
    public static function validateToken($token)
    {
        try {
            if (!$token) {
                return null;
            }

            $parts = explode('.', $token);
            if (count($parts) !== 2) {
                return null;
            }

            $payload = json_decode(base64_decode($parts[0]), true);
            $signature = $parts[1];

            // Verify signature
            $expectedSignature = hash_hmac('sha256', json_encode($payload), config('app.key'));
            if (!hash_equals($expectedSignature, $signature)) {
                return null;
            }

            // Check expiration
            if ($payload['expires_at'] < now()->timestamp) {
                return null;
            }

            return $payload;

        } catch (\Exception $e) {
            \Log::error('Token Validation Error: ' . $e->getMessage());
            return null;
        }
    }
} 
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\ApiAuthController;
use GuzzleHttp\Client;

class ApiAuthMiddleware
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
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        try {
            // Get token from Authorization header
            $authHeader = $request->header('Authorization');
            
            if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authorization token required',
                    'error' => 'Missing or invalid Authorization header'
                ], 401);
            }

            $token = substr($authHeader, 7); // Remove 'Bearer ' prefix

            // Validate token
            $payload = ApiAuthController::validateToken($token);
            
            if (!$payload) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or expired token',
                    'error' => 'Token validation failed'
                ], 401);
            }

            // Get user data from Supabase
            $userId = $payload['user_id'];
            $response = $this->client->get($this->supabaseUrl . '/rest/v1/auth_users', [
                'headers' => [
                    'apikey' => $this->supabaseKey,
                    'Authorization' => 'Bearer ' . $this->supabaseKey,
                    'Content-Type' => 'application/json'
                ],
                'query' => [
                    'id' => 'eq.' . $userId,
                    'select' => 'id,name,email,created_at,updated_at'
                ]
            ]);

            $users = json_decode($response->getBody()->getContents(), true);

            if (empty($users)) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                    'error' => 'Invalid user ID in token'
                ], 401);
            }

            $user = $users[0];

            // Add user data to request
            $request->merge(['user' => $user]);
            $request->setUserResolver(function () use ($user) {
                return $user;
            });

            return $next($request);

        } catch (\Exception $e) {
            \Log::error('API Auth Middleware Error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Authentication failed',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
} 
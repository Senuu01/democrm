<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Exception;

class SupabaseService
{
    private $baseUrl;
    private $apiKey;
    private $serviceKey;

    public function __construct()
    {
        $this->baseUrl = config('services.supabase.url');
        $this->apiKey = config('services.supabase.anon_key');
        $this->serviceKey = config('services.supabase.service_role_key');
    }

    public function query($table, $columns = '*', $filters = [])
    {
        $url = "{$this->baseUrl}/rest/v1/{$table}";
        
        $params = ['select' => $columns];
        foreach ($filters as $key => $value) {
            $params[$key] = "eq.{$value}";
        }

        $response = Http::withHeaders([
            'apikey' => $this->serviceKey,
            'Authorization' => "Bearer {$this->serviceKey}",
            'Content-Type' => 'application/json',
        ])->get($url, $params);

        return $response->json();
    }

    public function insert($table, $data)
    {
        $url = "{$this->baseUrl}/rest/v1/{$table}";

        $response = Http::withHeaders([
            'apikey' => $this->serviceKey,
            'Authorization' => "Bearer {$this->serviceKey}",
            'Content-Type' => 'application/json',
            'Prefer' => 'return=representation'
        ])->post($url, $data);

        return $response->json();
    }

    public function update($table, $id, $data)
    {
        $url = "{$this->baseUrl}/rest/v1/{$table}?id=eq.{$id}";

        $response = Http::withHeaders([
            'apikey' => $this->serviceKey,
            'Authorization' => "Bearer {$this->serviceKey}",
            'Content-Type' => 'application/json',
            'Prefer' => 'return=representation'
        ])->patch($url, $data);

        return $response->json();
    }

    public function delete($table, $id)
    {
        $url = "{$this->baseUrl}/rest/v1/{$table}?id=eq.{$id}";

        $response = Http::withHeaders([
            'apikey' => $this->serviceKey,
            'Authorization' => "Bearer {$this->serviceKey}",
        ])->delete($url);

        return $response->successful();
    }

    public function deleteAll($table)
    {
        $url = "{$this->baseUrl}/rest/v1/{$table}?id=not.is.null";

        $response = Http::withHeaders([
            'apikey' => $this->serviceKey,
            'Authorization' => "Bearer {$this->serviceKey}",
        ])->delete($url);

        return $response->successful();
    }

    public function signUp($email, $password)
    {
        $url = "{$this->baseUrl}/auth/v1/signup";

        $response = Http::withHeaders([
            'apikey' => $this->apiKey,
            'Content-Type' => 'application/json',
        ])->post($url, [
            'email' => $email,
            'password' => $password,
        ]);

        return $response->json();
    }

    public function signIn($email, $password)
    {
        $url = "{$this->baseUrl}/auth/v1/token?grant_type=password";

        $response = Http::withHeaders([
            'apikey' => $this->apiKey,
            'Content-Type' => 'application/json',
        ])->post($url, [
            'email' => $email,
            'password' => $password,
        ]);

        return $response->json();
    }

    public function getUser($token)
    {
        $url = "{$this->baseUrl}/auth/v1/user";

        $response = Http::withHeaders([
            'apikey' => $this->apiKey,
            'Authorization' => "Bearer {$token}",
        ])->get($url);

        return $response->json();
    }

    public function createUsersTable()
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS users (
                id SERIAL PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255) UNIQUE NOT NULL,
                company VARCHAR(255),
                created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
                updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
            );
        ";

        $response = Http::withHeaders([
            'apikey' => $this->serviceKey,
            'Authorization' => "Bearer {$this->serviceKey}",
            'Content-Type' => 'application/json',
        ])->post("{$this->baseUrl}/rest/v1/rpc/create_users_table", [
            'sql' => $sql
        ]);

        return $response->json();
    }
}
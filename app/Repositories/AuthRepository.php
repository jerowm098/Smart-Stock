<?php

namespace App\Repositories;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class AuthRepository
{
    protected string $url;
    protected string $key;
    protected string $serviceKey;

    public function __construct()
    {
        $this->url = rtrim((string) config('services.supabase.url'), '/');
        $this->key = (string) config('services.supabase.anon_key');
        $this->serviceKey = (string) config('services.supabase.service_key');
    }

    /**
     * Find a user by email.
     */
    public function findByEmail(string $email): ?array
    {
        $response = Http::withHeaders([
            'apikey' => $this->serviceKey,
            'Authorization' => 'Bearer ' . $this->serviceKey,
            'Prefer' => 'return=representation',
        ])->get($this->url . '/rest/v1/users?email=eq.' . urlencode($email));

        if ($response->failed()) {
            Log::error('AuthRepository: find user failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return null;
        }

        $data = $response->json();
        return is_array($data) && count($data) > 0 ? $data[0] : null;
    }

    /**
     * Register a new user.
     */
    public function register(array $data): array
    {
        $data['password'] = Hash::make($data['password']);

        $response = Http::withHeaders([
            'apikey' => $this->serviceKey,
            'Authorization' => 'Bearer ' . $this->serviceKey,
            'Content-Type' => 'application/json',
            'Prefer' => 'return=representation',
        ])->post($this->url . '/rest/v1/users', [
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
        ]);

        if ($response->failed()) {
            $body = $response->body();
            
            // Check for unique constraint violation
            if (str_contains($body, 'duplicate') || str_contains($body, 'unique')) {
                throw new \Exception('Email already registered');
            }
            
            Log::error('AuthRepository: register failed', [
                'status' => $response->status(),
                'body' => $body,
            ]);
            throw new RuntimeException('Registration failed: ' . $body);
        }

        return $response->json()[0];
    }

    /**
     * Authenticate user by email and password.
     * Returns user data if successful, null otherwise.
     */
    public function authenticate(string $email, string $password): ?array
    {
        $user = $this->findByEmail($email);
        
        if (!$user) {
            return null;
        }

        if (!Hash::check($password, $user['password'])) {
            return null;
        }

        return $user;
    }
}

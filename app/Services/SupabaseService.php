<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class SupabaseService
{
    protected string $url;

    protected string $key;

    public function __construct()
    {
        $this->url = rtrim((string) config('services.supabase.url'), '/');
        $this->key = (string) config('services.supabase.anon_key');
    }

    /**
     * Insert a row into a Supabase (PostgREST) table.
     *
     * @return array<string, mixed>
     */
    public function insert(string $table, array $data): array
    {
        $response = Http::withHeaders([
            'apikey' => $this->key,
            'Authorization' => 'Bearer '.$this->key,
            'Content-Type' => 'application/json',
            'Prefer' => 'return=representation',
        ])->post($this->url.'/rest/v1/'.$table, $data);

        if ($response->failed()) {
            Log::error('Supabase insert failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new RuntimeException('Supabase insert failed: '.$response->body());
        }

        return $response->json();
    }

    /**
     * List all rows from a Supabase (PostgREST) table.
     *
     * @return array<int, array<string, mixed>>|null
     */
    public function list(string $table): ?array
    {
        $response = Http::withHeaders([
            'apikey' => $this->key,
            'Authorization' => 'Bearer '.$this->key,
            'Content-Type' => 'application/json',
        ])->get($this->url.'/rest/v1/'.$table);

        if ($response->failed()) {
            Log::error('Supabase list failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new RuntimeException('Supabase list failed: '.$response->body());
        }

        return $response->json();
    }
}

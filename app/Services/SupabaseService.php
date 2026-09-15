<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use RuntimeException;

class SupabaseService
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

    /**
     * Execute raw SQL against Supabase using the SQL API (requires service role key).
     *
     * @return array<int, array<string, mixed>>|array<string, mixed>|null
     */
    public function executeSql(string $sql): mixed
    {
        if ($this->serviceKey === '') {
            throw new RuntimeException('SUPABASE_SERVICE_KEY is not configured in .env');
        }

        $response = Http::withHeaders([
            'apikey' => $this->serviceKey,
            'Authorization' => 'Bearer '.$this->serviceKey,
            'Content-Type' => 'application/json',
        ])->post($this->url.'/rest/v1/rpc/exec_sql', ['sql' => $sql]);

        if ($response->failed()) {
            Log::error('Supabase SQL execution failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new RuntimeException('Supabase SQL execution failed: '.$response->body());
        }

        return $response->json();
    }

    /**
     * Read and execute the SQL from database/dev.sql to update the schema.
     */
    public function updateDatabase(): void
    {
        $sql = $this->getDevSql();
        $this->executeSql($sql);
    }

    /**
     * Reset the public schema and re-run database/dev.sql.
     */
    public function resetDatabase(): void
    {
        $resetSql = "drop schema public cascade; create schema public; grant all on schema public to postgres; grant all on schema public to anon; grant all on schema public to authenticated; grant all on schema public to service_role;";
        $this->executeSql($resetSql);

        $sql = $this->getDevSql();
        $this->executeSql($sql);
    }

    /**
     * Get the SQL content from database/dev.sql.
     */
    protected function getDevSql(): string
    {
        $path = base_path('database/dev.sql');

        if (! File::exists($path)) {
            throw new RuntimeException('database/dev.sql not found at: '.$path);
        }

        return File::get($path);
    }
}

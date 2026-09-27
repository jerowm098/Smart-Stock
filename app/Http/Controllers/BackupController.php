<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * SS-39: Regular backups — Admin-only backup management.
 *
 * BRD Should: "The system shall create backups regularly."
 *
 * - Page: GET /backups → resources/views/backups.blade.php (Admin Dashboard side)
 * - Auto-schedule: daily 02:00 via routes/console.php → backup:run --keep=7
 * - Manual: POST /api/backups/run (Admin clicks "Run Backup Now")
 * - Files live in storage/app/private/backups (never public).
 */
class BackupController extends Controller
{
    protected function backupDir(): string
    {
        $dir = storage_path('app/private/backups');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        return $dir;
    }

    protected function isValidName(string $name): bool
    {
        // Only allow files this feature creates: smart-stock-backup-YYYYMMDD-HHMMSS.(sqlite|json)
        return (bool) preg_match('/^smart-stock-backup-\d{8}-\d{6}\.(sqlite|json)$/', $name);
    }

    /**
     * Show the Backups page (Admin Dashboard side).
     */
    public function index(): \Illuminate\View\View
    {
        return view('backups');
    }

    /**
     * List all backup files (newest first).
     */
    public function list(): JsonResponse
    {
        $user = Auth::user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $dir = $this->backupDir();
        $files = glob($dir . DIRECTORY_SEPARATOR . 'smart-stock-backup-*') ?: [];
        rsort($files);

        $items = array_map(function ($path) {
            return [
                'name' => basename($path),
                'size_bytes' => filesize($path),
                'size_human' => $this->humanSize((int) filesize($path)),
                'modified' => date('M d, Y g:i A', (int) filemtime($path)),
                'type' => str_ends_with($path, '.sqlite') ? 'SQLite copy' : 'JSON dump',
            ];
        }, $files);

        return response()->json([
            'count' => count($items),
            'backups' => $items,
            'schedule' => 'Auto daily 02:00 (Asia/Manila) · keep newest 7',
            'driver' => config('database.default') . ' / ' . config('database.connections.' . config('database.default') . '.driver'),
        ]);
    }

    /**
     * Run a backup now (Admin button).
     */
    public function run(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $keep = (int) $request->input('keep', 7);
        $keep = max(1, min(30, $keep));

        $exit = Artisan::call('backup:run', ['--keep' => $keep]);

        return response()->json([
            'success' => $exit === 0,
            'output' => trim(Artisan::output()),
        ], $exit === 0 ? 200 : 500);
    }

    /**
     * Download one backup file.
     */
    public function download(string $file): BinaryFileResponse|JsonResponse
    {
        if (! $this->isValidName($file)) {
            return response()->json(['message' => 'Invalid backup filename.'], 422);
        }

        $path = $this->backupDir() . DIRECTORY_SEPARATOR . $file;
        if (! is_file($path)) {
            return response()->json(['message' => 'Backup not found.'], 404);
        }

        return response()->download($path, $file);
    }

    /**
     * Delete one backup file.
     */
    public function destroy(string $file): JsonResponse
    {
        if (! $this->isValidName($file)) {
            return response()->json(['message' => 'Invalid backup filename.'], 422);
        }

        $path = $this->backupDir() . DIRECTORY_SEPARATOR . $file;
        if (! is_file($path)) {
            return response()->json(['message' => 'Backup not found.'], 404);
        }

        @unlink($path);

        return response()->json(['message' => 'Backup deleted.', 'file' => $file]);
    }

    protected function humanSize(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' B';
        }
        $units = ['KB', 'MB', 'GB'];
        $i = 0;
        $size = $bytes / 1024;
        while ($size >= 1024 && $i < count($units) - 1) {
            $size /= 1024;
            $i++;
        }

        return round($size, 1) . ' ' . $units[$i];
    }
}

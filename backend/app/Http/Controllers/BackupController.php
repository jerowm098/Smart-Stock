<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * SS-39: Regular backups — Admin-only backup management.
 *
 * BRD Should: "The system shall create backups regularly."
 *
 * - Primary: Supabase PostgreSQL only (walang SQLite).
 * - Page: GET /backups → resources/views/backups.blade.php (Admin Dashboard side)
 * - Auto-schedule: daily 02:00 via routes/console.php → backup:run --keep=7
 * - Manual: POST /api/backups/run (Admin clicks "Run Backup Now")
 * - Dumps are stored in the `backups` table (survives Render restarts) and
 *   also written to storage/app/private/backups as a local convenience copy.
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
        // Only allow files this feature creates: smart-stock-backup-YYYYMMDD-HHMMSS.json
        return (bool) preg_match('/^smart-stock-backup-\d{8}-\d{6}\.json$/', $name);
    }

    /**
     * List all backups (newest first), merging database rows with any files
     * left on disk (e.g. backups created before the database store existed).
     */
    public function list(): JsonResponse
    {
        $user = Auth::user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $items = [];

        if (Schema::hasTable('backups')) {
            foreach (DB::table('backups')->get() as $row) {
                $items[$row->filename] = [
                    'name' => $row->filename,
                    'size_bytes' => (int) $row->size_bytes,
                    'size_human' => $this->humanSize((int) $row->size_bytes),
                    'modified' => date('M d, Y g:i A', (int) strtotime((string) $row->created_at)),
                    'type' => 'Database dump',
                ];
            }
        }

        $dir = $this->backupDir();
        $files = glob($dir . DIRECTORY_SEPARATOR . 'smart-stock-backup-*') ?: [];
        foreach ($files as $path) {
            $name = basename($path);
            if (isset($items[$name])) {
                continue;
            }

            $items[$name] = [
                'name' => $name,
                'size_bytes' => (int) filesize($path),
                'size_human' => $this->humanSize((int) filesize($path)),
                'modified' => date('M d, Y g:i A', (int) filemtime($path)),
                'type' => 'Database dump',
            ];
        }

        // Filenames are timestamps (Ymd-His), so reverse key sort = newest first.
        krsort($items);

        return response()->json([
            'count' => count($items),
            'backups' => array_values($items),
            'schedule' => 'Automatic backup daily at 2:00 AM (Manila)',
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
     * Download one backup.
     */
    public function download(string $file): BinaryFileResponse|JsonResponse|Response
    {
        if (! $this->isValidName($file)) {
            return response()->json(['message' => 'Invalid backup filename.'], 422);
        }

        // Database copy first — this is the one that survives redeploys.
        if (Schema::hasTable('backups')) {
            $row = DB::table('backups')->where('filename', $file)->first();
            if ($row) {
                return response((string) $row->content, 200, [
                    'Content-Type' => 'application/json',
                    'Content-Disposition' => 'attachment; filename="' . $file . '"',
                ]);
            }
        }

        // Fall back to a file on disk.
        $path = $this->backupDir() . DIRECTORY_SEPARATOR . $file;
        if (! is_file($path)) {
            return response()->json(['message' => 'Backup not found.'], 404);
        }

        return response()->download($path, $file);
    }

    /**
     * Delete one backup.
     */
    public function destroy(string $file): JsonResponse
    {
        if (! $this->isValidName($file)) {
            return response()->json(['message' => 'Invalid backup filename.'], 422);
        }

        $deleted = false;

        if (Schema::hasTable('backups')) {
            $deleted = DB::table('backups')->where('filename', $file)->delete() > 0;
        }

        $path = $this->backupDir() . DIRECTORY_SEPARATOR . $file;
        if (is_file($path)) {
            @unlink($path);
            $deleted = true;
        }

        if (! $deleted) {
            return response()->json(['message' => 'Backup not found.'], 404);
        }

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

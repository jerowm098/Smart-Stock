<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class SystemResetController extends Controller
{
    /**
     * Clear all operational, catalog, audit, backup, and other-user data
     * across the system after verifying the signed-in admin's password.
     * The currently signed-in admin account is always preserved.
     */
    public function clearAll(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $validated = $request->validate([
            'password' => ['required', 'string'],
        ]);

        if (! Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'message' => 'The password you entered is incorrect.',
                'errors' => [
                    'password' => ['The password you entered is incorrect.'],
                ],
            ], 422);
        }

        $tablesInDeleteOrder = [
            'sale_items',
            'sales',
            'stock_ins',
            'stock_adjustments',
            'order_suggestions',
            'alerts',
            'products',
            'categories',
            'suppliers',
            'activity_logs',
            'backups',
        ];

        $cleared = [];

        DB::transaction(function () use ($tablesInDeleteOrder, $user, &$cleared) {
            foreach ($tablesInDeleteOrder as $table) {
                if (Schema::hasTable($table)) {
                    $cleared[$table] = DB::table($table)->delete();
                }
            }

            if (Schema::hasTable('users')) {
                $cleared['users'] = DB::table('users')
                    ->where('id', '!=', $user->id)
                    ->delete();
            }
        });

        // Clean up backup files on disk as well
        $backupDir = storage_path('app/backups');
        if (is_dir($backupDir)) {
            foreach (glob($backupDir . DIRECTORY_SEPARATOR . 'smart-stock-backup-*') ?: [] as $path) {
                @unlink($path);
            }
        }

        return response()->json([
            'message' => 'All system data has been cleared. Your admin account was kept intact.',
            'cleared' => $cleared,
        ]);
    }
}

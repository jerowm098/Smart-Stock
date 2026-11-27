<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ActivityLogController extends Controller
{
    /**
     * Recent Activity feed for the Products page.
     *
     * Returns the most recent admin actions (product add/edit/deactivate/reactivate,
     * stock adjust) newest-first, each with the acting user's name so the UI can
     * render "who did what, when" as a vertical row list.
     *
     * Each page owns its own feed: Products shows product actions only, Stock
     * Inventory passes ?actions=stock_received, and sales live on Transactions.
     * Stock-received and sale rows written by older builds are filtered out of
     * this default feed so they stay hidden.
     *
     * Admin-only: it exposes staff activity and inventory movements, matching
     * the BRD rule that staff must never read admin dashboard data.
     */
    public function recent(Request $request): JsonResponse
    {
        if (! Auth::check()) {
            return response()->json(['message' => 'Authentication required.'], 401);
        }

        $limit = (int) $request->query('limit', 8);
        $limit = max(1, min($limit, 50));

        // Optional comma-separated action filter, e.g. ?actions=stock_received,stock_adjusted
        $actions = array_filter(array_map('trim', explode(',', $request->query('actions', ''))));

        $query = ActivityLog::with('user:id,name,role')->latest();
        if (!empty($actions)) {
            $query->whereIn('action', $actions);
        } else {
            // Default feed (Products "Recent Activity"): product-page actions only.
            // Stock receipts belong to Stock Inventory; sales belong to Transactions;
            // suppliers have their own feed on the Suppliers page.
            $query->whereNotIn('action', [
                'sale_completed',
                'transactions_exported',
                'stock_received',
                'supplier_created',
                'supplier_updated',
                'supplier_removed',
                'category_created',
                'category_updated',
                'category_deleted',
                'user_created',
                'user_updated',
                'user_deactivated',
                'user_reactivated',
                'user_password_reset',
                'backup_created',
                'backup_deleted',
                'backup_downloaded',
            ]);
        }

        $rows = $query->limit($limit)->get()
            ->map(fn (ActivityLog $log) => [
                'id' => $log->id,
                'action' => $log->action,
                'description' => $log->description,
                'subject_type' => $log->subject_type,
                'subject_id' => $log->subject_id,
                'subject_name' => $log->subject_name,
                'meta' => $log->meta,
                'user_name' => $log->user?->name ?? 'Unknown user',
                'user_role' => $log->user?->role,
                'created_at' => optional($log->created_at)->toISOString(),
                'created_at_human' => optional($log->created_at)->diffForHumans(),
            ]);

        return response()->json($rows);
    }

    /**
     * Clear a page-scoped slice of the activity feed.
     *
     * Destructive: permanently deletes the matching activity_logs rows. The
     * caller must name the actions explicitly (e.g. ?actions=stock_received
     * from Stock Inventory) so a single page can never wipe the whole trail by
     * accident. Unknown action names are ignored; no recognised action = 422.
     *
     * Admin-only, like the read endpoint.
     */
    public function clear(Request $request): JsonResponse
    {
        if (! Auth::check()) {
            return response()->json(['message' => 'Authentication required.'], 401);
        }

        $allowed = [
            'product_created',
            'product_updated',
            'product_deactivated',
            'product_reactivated',
            'stock_received',
            'stock_adjusted',
            'sale_completed',
            'transactions_exported',
            'suggestion_ordered',
            'suggestion_dismissed',
            'supplier_created',
            'supplier_removed',
            'user_created',
            'user_updated',
            'user_deactivated',
            'user_reactivated',
            'user_password_reset',
            'backup_created',
            'backup_deleted',
            'backup_downloaded',
        ];

        $actions = array_values(array_intersect(
            array_filter(array_map('trim', explode(',', $request->query('actions', '')))),
            $allowed,
        ));

        if (empty($actions)) {
            return response()->json([
                'message' => 'A valid actions filter is required to clear the activity log.',
            ], 422);
        }

        $deleted = ActivityLog::whereIn('action', $actions)->delete();

        return response()->json([
            'message' => 'Activity log cleared.',
            'deleted' => $deleted,
        ]);
    }
}

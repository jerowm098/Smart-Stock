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
            'category_created',
            'category_updated',
            'category_deleted',
            'supplier_created',
            'supplier_updated',
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

    /**
     * SS-24 / SS-107: Centralized Audit Logs table with date-range, module, and search filters.
     *
     * Admin-only paginated view across ALL recorded activity_logs rows.
     */
    public function logs(Request $request): JsonResponse
    {
        if (! Auth::check()) {
            return response()->json(['message' => 'Authentication required.'], 401);
        }

        $perPage = max(1, min((int) $request->query('per_page', 6), 50));
        $page = max(1, (int) $request->query('page', 1));
        $search = trim((string) $request->query('search', ''));
        $module = trim((string) $request->query('module', ''));
        $dateFrom = trim((string) $request->query('date_from', ''));
        $dateTo = trim((string) $request->query('date_to', ''));

        $moduleMap = [
            'product'     => ['product_created', 'product_updated', 'product_deactivated', 'product_reactivated'],
            'stock'       => ['stock_received', 'stock_adjusted'],
            'category'    => ['category_created', 'category_updated', 'category_deleted'],
            'supplier'    => ['supplier_created', 'supplier_updated', 'supplier_removed'],
            'transaction' => ['sale_completed', 'transactions_exported'],
            'suggestion'  => ['suggestion_ordered', 'suggestion_dismissed'],
            'user'        => ['user_created', 'user_updated', 'user_deactivated', 'user_reactivated', 'user_password_reset'],
            'backup'      => ['backup_created', 'backup_deleted', 'backup_downloaded'],
        ];

        $query = ActivityLog::with('user:id,name,role')->latest('id');

        if ($module !== '' && isset($moduleMap[$module])) {
            $query->whereIn('action', $moduleMap[$module]);
        }

        if ($dateFrom !== '') {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo !== '') {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        if ($search !== '') {
            $like = '%' . $search . '%';
            $query->where(function ($q) use ($like) {
                $q->where('description', 'like', $like)
                    ->orWhere('subject_name', 'like', $like)
                    ->orWhere('action', 'like', $like)
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', $like));
            });
        }

        $total = (clone $query)->count();
        $lastPage = max(1, (int) ceil($total / $perPage));
        $safePage = min($page, $lastPage);

        $rows = $query
            ->skip(($safePage - 1) * $perPage)
            ->take($perPage)
            ->get()
            ->map(function (ActivityLog $log) {
                $moduleLabel = match (true) {
                    str_starts_with($log->action, 'product_') => 'Products',
                    str_starts_with($log->action, 'stock_') => 'Stock Inventory',
                    str_starts_with($log->action, 'category_') => 'Categories',
                    str_starts_with($log->action, 'supplier_') => 'Suppliers',
                    str_starts_with($log->action, 'sale_'), str_starts_with($log->action, 'transactions_') => 'Transactions',
                    str_starts_with($log->action, 'suggestion_') => 'Order Suggestions',
                    str_starts_with($log->action, 'user_') => 'Users',
                    str_starts_with($log->action, 'backup_') => 'Backups',
                    default => ucfirst((string) ($log->subject_type ?? 'System')),
                };

                return [
                    'id'               => $log->id,
                    'action'           => $log->action,
                    'action_label'     => ucfirst(str_replace('_', ' ', $log->action)),
                    'module'           => $moduleLabel,
                    'description'      => $log->description,
                    'subject_type'     => $log->subject_type,
                    'subject_id'       => $log->subject_id,
                    'subject_name'     => $log->subject_name,
                    'meta'             => $log->meta,
                    'user_name'        => $log->user?->name ?? 'System',
                    'user_role'        => $log->user?->role ?? 'admin',
                    'timestamp'        => optional($log->created_at)->format('M d, Y g:i A'),
                    'created_at'       => optional($log->created_at)->toISOString(),
                    'created_at_human' => optional($log->created_at)->diffForHumans(),
                ];
            });

        return response()->json([
            'logs'      => $rows,
            'total'     => $total,
            'page'      => $safePage,
            'per_page'  => $perPage,
            'last_page' => $lastPage,
        ]);
    }
}

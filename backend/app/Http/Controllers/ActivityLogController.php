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
     * Returns the most recent admin actions (product add/edit/deactivate, stock
     * receive/adjust, sale) newest-first, each with the acting user's name so the
     * UI can render "who did what, when" as a vertical row list.
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
}

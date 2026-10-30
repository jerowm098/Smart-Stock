<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ActivityLog — a single row in the human-readable "Recent Activity" feed.
 *
 * Rows are written by App\Services\ActivityLogger (never created directly from
 * controllers) so every action is described consistently. Read back by the
 * activity feed endpoint and rendered as a vertical row list on the Products
 * page.
 *
 * @property int $id
 * @property int|null $user_id The account that performed the action
 * @property string $action Machine key (product_updated, stock_adjusted, …)
 * @property string|null $subject_type Target kind (product, sale, …)
 * @property int|null $subject_id Target primary key
 * @property string|null $subject_name Target display name at action time
 * @property string $description Ready-to-render sentence
 * @property array|null $meta Structured detail (deltas, before/after, …)
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read User|null $user
 */
class ActivityLog extends Model
{
    protected $table = 'activity_logs';

    protected $fillable = [
        'user_id',
        'action',
        'subject_type',
        'subject_id',
        'subject_name',
        'description',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'subject_id' => 'integer',
            'meta' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

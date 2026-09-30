<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * BRD (Demand Forecasting & Order Suggestions) — "Suggestion Record Lifecycle".
 *
 * status: active -> ordered | dismissed
 *
 * Rows are written by the nightly `forecast:orders` job so the dashboard never
 * has to compute forecasts on the fly.
 *
 * @property int $id
 * @property int $product_id
 * @property string $status
 * @property float $avg_daily
 * @property float $reorder_point
 * @property float $suggested_qty
 * @property int $current_stock
 * @property int $window_days
 * @property string $urgency
 * @property string|null $reason
 * @property \Illuminate\Support\Carbon|null $generated_at
 * @property \Illuminate\Support\Carbon|null $actioned_at
 * @property int|null $actioned_by
 * @property-read Product|null $product
 * @property-read User|null $actionedByUser
 */
class OrderSuggestion extends Model
{
    public const STATUS_ACTIVE   = 'active';
    public const STATUS_ORDERED  = 'ordered';
    public const STATUS_DISMISSED = 'dismissed';

    protected $table = 'order_suggestions';

    protected $fillable = [
        'product_id',
        'status',
        'avg_daily',
        'reorder_point',
        'suggested_qty',
        'current_stock',
        'window_days',
        'urgency',
        'reason',
        'generated_at',
        'actioned_at',
        'actioned_by',
    ];

    protected function casts(): array
    {
        return [
            'avg_daily'     => 'float',
            'reorder_point' => 'float',
            'suggested_qty' => 'float',
            'current_stock' => 'integer',
            'window_days'   => 'integer',
            'generated_at'  => 'datetime',
            'actioned_at'   => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function actionedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actioned_by');
    }

    /** Only suggestions still awaiting the Admin's decision. */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }
}

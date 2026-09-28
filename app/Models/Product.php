<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Product model for the Smart-Stock inventory system.
 *
 * @property int $id Primary key
 * @property int|null $user_id Account that first created the product (audit trail only).
 *                                The catalogue itself is SHARED across every role — this
 *                                column is deliberately NOT used to isolate data.
 * @property string $name Product name
 * @property string|null $description Product description
 * @property string $sku Unique stock keeping unit
 * @property string|null $category Product category
 * @property string $price Product price (decimal)
 * @property int $current_stock Current stock quantity
 * @property int $reorder_threshold Stock level that triggers reorder alerts
 * @property string|null $receiving_unit Default receiving unit of measure (e.g. box, bag, roll)
 * @property int $pieces_per_receiving_unit Number of base pieces contained in one receiving unit
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read User|null $owner
 */
class Product extends Model
{
    /** @use HasFactory<\Database\Factories\ProductFactory> */
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'products';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'description',
        'sku',
        'category',
        'price',
        'current_stock',
        'reorder_threshold',
        'receiving_unit',
        'pieces_per_receiving_unit',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'current_stock' => 'integer',
            'reorder_threshold' => 'integer',
            'pieces_per_receiving_unit' => 'integer',
        ];
    }

    /**
     * Get the user who created this product (audit trail only).
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

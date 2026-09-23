<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * StockIn model for the Smart-Stock inventory system.
 *
 * SS-40 / SS-89: Persists every stock-in (receiving) transaction so the
 * audit trail (SS-24) captures what was received, from which supplier,
 * in which unit of measure, the effective piece delta applied to
 * inventory, and the staff member who logged it.
 *
 * @property int $id Primary key
 * @property int|null $product_id Received product
 * @property int|null $user_id Staff member who logged the stock-in
 * @property int|null $supplier_id Supplier the goods came from
 * @property int $quantity_received Quantity received in the given unit
 * @property string $unit_of_measure Unit used at receiving time
 * @property int $unit_conversion Pieces per received unit
 * @parameter int $piece_delta Effective piece delta applied to inventory
 * @property int $stock_before Stock count before the stock-in
 * @property int $stock_after Stock count after the stock-in
 * @property string|null $note Optional receiving note
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read Product|null $product
 * @property-read User|null $staff
 * @property-read Supplier|null $supplier
 */
class StockIn extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'stock_ins';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'product_id',
        'user_id',
        'supplier_id',
        'quantity_received',
        'unit_of_measure',
        'unit_conversion',
        'piece_delta',
        'stock_before',
        'stock_after',
        'note',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'product_id' => 'integer',
            'user_id' => 'integer',
            'supplier_id' => 'integer',
            'quantity_received' => 'integer',
            'unit_conversion' => 'integer',
            'piece_delta' => 'integer',
            'stock_before' => 'integer',
            'stock_after' => 'integer',
        ];
    }

    /**
     * Get the product that was received.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the staff member who logged this stock-in.
     */
    public function staff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the supplier this stock-in was received from.
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
}
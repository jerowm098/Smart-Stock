<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Sale model for the Smart-Stock POS checkout flow.
 *
 * @property int $id Primary key
 * @property int $user_id Cashier who completed the sale
 * @property float $total_amount Total cart amount charged
 * @property float $payment_amount Amount tendered by the customer
 * @property float $change_amount Change returned to the customer
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class Sale extends Model
{
    /** @use HasFactory<\Database\Factories\SaleFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'total_amount',
        'payment_amount',
        'change_amount',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'payment_amount' => 'decimal:2',
            'change_amount' => 'decimal:2',
        ];
    }

    /**
     * Get the line items that belong to this sale.
     */
    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    /**
     * Get the cashier who completed this sale.
     */
    public function cashier()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
<?php

namespace Database\Factories;

use App\Models\StockIn;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockIn>
 */
class StockInFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = StockIn::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => null,
            'user_id' => null,
            'supplier_id' => null,
            'quantity_received' => 1,
            'unit_of_measure' => 'piece',
            'unit_conversion' => 1,
            'piece_delta' => 1,
            'stock_before' => 0,
            'stock_after' => 1,
            'note' => null,
        ];
    }
}
<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Product::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->words(3, true),
            'sku' => 'SKU-'.fake()->unique()->numerify('#####'),
            'category' => fake()->randomElement(['Electronics', 'Hardware', 'Accessories']),
            'price' => fake()->randomFloat(2, 10, 5000),
            'current_stock' => fake()->numberBetween(0, 100),
            'reorder_threshold' => fake()->numberBetween(1, 20),
            'receiving_unit' => fake()->randomElement(['piece', 'box', 'bag', 'roll', 'pack', 'case']),
            'pieces_per_receiving_unit' => fake()->randomElement([1, 6, 12, 24, 50, 100]),
        ];
    }
}

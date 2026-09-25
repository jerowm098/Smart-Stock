<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // SS-49: Seed the products table with a realistic hardware-store
        // inventory dataset so the catalogue (SS-17) and search/filter
        // features (SS-18) have real data to display and test against.
        $this->call(ProductSeeder::class);
        $this->call(SupplierSeeder::class);
    }
}

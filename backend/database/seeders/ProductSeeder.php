<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * SS-49: Seed the products table with a realistic hardware-store
 * inventory dataset so the catalogue (SS-17) and search/filter
 * features (SS-18) have real data to display and test against.
 *
 * Items are attached to the default admin/test user so the seeded
 * catalogue is visible on the authenticated dashboard and products
 * pages.
 */
class ProductSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Sample hardware inventory items.
     *
     * Each entry mirrors the columns created by the products migration:
     * name, sku, category, price, current_stock, reorder_threshold.
     *
     * @var list<array<string, mixed>>
     */
    protected array $items = [
        ['name' => 'Cordless Drill 18V',        'sku' => 'DRILL-CDL-18V',  'category' => 'Power Tools',    'price' => 1299.00, 'current_stock' => 14, 'reorder_threshold' => 5],
        ['name' => 'Hammer Claw 16oz',          'sku' => 'HAMMER-CLW-16',  'category' => 'Hand Tools',     'price' => 350.00,  'current_stock' => 42, 'reorder_threshold' => 10],
        ['name' => 'Screwdriver Set 8-in-1',    'sku' => 'SCREW-SET-08',   'category' => 'Hand Tools',     'price' => 499.00,  'current_stock' => 8,  'reorder_threshold' => 4],
        ['name' => 'LED Work Flashlight',       'sku' => 'FLASH-LED-WK',   'category' => 'Electronics',    'price' => 799.00,  'current_stock' => 3,  'reorder_threshold' => 5],
        ['name' => 'Tape Measure 25ft',         'sku' => 'TAPE-MEAS-25',   'category' => 'Measuring',      'price' => 199.00,  'current_stock' => 0,  'reorder_threshold' => 6],
        ['name' => 'PVC Pipe 1in x 10ft',       'sku' => 'PVC-1IN-10FT',   'category' => 'Plumbing',       'price' => 120.00,  'current_stock' => 55, 'reorder_threshold' => 12],
        ['name' => 'Copper Wire 12 AWG',        'sku' => 'WIRE-COP-12',    'category' => 'Electrical',     'price' => 850.00,  'current_stock' => 2,  'reorder_threshold' => 4],
        ['name' => 'Paint Roller 9in',          'sku' => 'PNT-ROLL-09',    'category' => 'Painting',       'price' => 95.00,   'current_stock' => 60, 'reorder_threshold' => 15],
        ['name' => 'Level Spirit 24in',         'sku' => 'LEVEL-SPI-24',   'category' => 'Measuring',      'price' => 320.00,  'current_stock' => 11, 'reorder_threshold' => 5],
        ['name' => 'Socket Wrench 1/2in Drive', 'sku' => 'SOCK-WRENCH-12', 'category' => 'Hand Tools',     'price' => 540.00,  'current_stock' => 1,  'reorder_threshold' => 3],
        ['name' => 'Drill Bit Set 29pc',        'sku' => 'BIT-SET-29',     'category' => 'Power Tools',    'price' => 680.00,  'current_stock' => 9,  'reorder_threshold' => 4],
        ['name' => 'Extension Cord 50ft',       'sku' => 'EXT-CORD-50',    'category' => 'Electrical',     'price' => 1150.00, 'current_stock' => 18, 'reorder_threshold' => 6],
        ['name' => 'Safety Goggles',            'sku' => 'SAFE-GLS-01',    'category' => 'Safety',         'price' => 175.00,  'current_stock' => 0,  'reorder_threshold' => 10],
        ['name' => 'Stud Finder',               'sku' => 'STUD-FIND-01',   'category' => 'Measuring',      'price' => 299.00,  'current_stock' => 7,  'reorder_threshold' => 4],
        ['name' => 'Pipe Wrench 14in',          'sku' => 'PIPE-WREN-14',   'category' => 'Plumbing',       'price' => 430.00,  'current_stock' => 13, 'reorder_threshold' => 5],
    ];

    /**
     * Run the database seeds.
     *
     * The sample catalogue is seeded for every user that already exists
     * in the database, including newly created accounts, so each account
     * starts with a realistic hardware inventory to display and test.
     */
    public function run(): void
    {
        $users = User::query()->get();

        if ($users->isEmpty()) {
            $users = collect([User::factory()->create([
                'name'  => 'Smart-Stock Admin',
                'email' => 'admin@smart-stock.local',
                'role'  => 'admin',
            ])]);
        }

        foreach ($users as $owner) {
            $this->seedForUser($owner->id);
        }
    }

    /**
     * Seed the sample catalogue for a single user.
     *
     * Used by the registration flow so that every new account
     * immediately has the sample hardware inventory to display
     * and test against.
     */
    public function seedForUser(int $userId): void
    {
        foreach ($this->items as $item) {
            Product::query()->updateOrCreate(
                ['user_id' => $userId, 'sku' => $item['sku']],
                array_merge($item, ['user_id' => $userId]),
            );
        }
    }
}

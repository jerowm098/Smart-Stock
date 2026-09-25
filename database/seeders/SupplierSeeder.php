<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    /**
     * Seed the suppliers table with sample data.
     */
    public function run(): void
    {
        $suppliers = [
            [
                'name'           => 'Manila Hardware Supply',
                'contact_person' => 'Ricardo Santos',
                'phone'          => '09171234567',
                'email'          => 'ricardo@manilahardware.com',
                'is_active'      => true,
            ],
            [
                'name'           => 'Cebu Steel Corporation',
                'contact_person' => 'Maria Reyes',
                'phone'          => '09221234567',
                'email'          => 'maria@cebusteel.com',
                'is_active'      => true,
            ],
            [
                'name'           => 'Davao Paint Center',
                'contact_person' => 'Juan Bautista',
                'phone'          => '09331234567',
                'email'          => 'juan@davaopaint.com',
                'is_active'      => true,
            ],
            [
                'name'           => 'Luzon Electrical Supply',
                'contact_person' => 'Ana Villanueva',
                'phone'          => '09181234567',
                'email'          => 'ana@luzonelectrical.com',
                'is_active'      => true,
            ],
            [
                'name'           => 'Metro Plumbing Depot',
                'contact_person' => 'Carlos Garcia',
                'phone'          => '09191234567',
                'email'          => 'carlos@metroplumbing.com',
                'is_active'      => true,
            ],
            [
                'name'           => 'Visayas Cement Works',
                'contact_person' => 'Elena Cruz',
                'phone'          => '09201234567',
                'email'          => 'elena@visayascement.com',
                'is_active'      => true,
            ],
            [
                'name'           => 'Lugawan Tools & Equipment',
                'contact_person' => 'Pedro Mendoza',
                'phone'          => '09211234567',
                'email'          => 'pedro@lugawantools.com',
                'is_active'      => true,
            ],
        ];

        foreach ($suppliers as $supplier) {
            Supplier::create($supplier);
        }
    }
}

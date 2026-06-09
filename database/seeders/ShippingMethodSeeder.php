<?php

namespace Database\Seeders;

use App\Models\ShippingMethod;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ShippingMethodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ShippingMethod::insert([
            [
                'name' => 'Standard Shipping',
                'status' => 1,
            ],
            [
                'name' => 'Express Shipping',
                'status' => false,
            ],
        ]);
    }
}

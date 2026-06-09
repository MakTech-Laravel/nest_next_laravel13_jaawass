<?php

namespace Database\Seeders;

use App\Models\FaqCategory;
use Illuminate\Database\Seeder;

class FaqCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'General',
                'slug' => 'general',
            ],
            [
                'name' => 'Technical',
                'slug' => 'technical',
            ],
            [
                'name' => 'Billing',
                'slug' => 'billing',
            ],
        ];

      FaqCategory::insert($categories);
    }
}

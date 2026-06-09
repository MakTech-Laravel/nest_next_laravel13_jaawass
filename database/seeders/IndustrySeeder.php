<?php

namespace Database\Seeders;

use App\Models\Industry;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class IndustrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $industries = [
            [
                'name' => 'Manufacturing',
               
                'slug' => 'manufacturing',
            ],
            [
                'name' => 'Construction',
                'slug' => 'construction',
            ],
            [
                'name' => 'Agriculture',
                'slug' => 'agriculture',
            ],
            [
                'name' => 'Mining',
                'slug' => 'mining',
            ],
            [
                'name' => 'Energy',
                'slug' => 'energy',
            ],
            [
                'name' => 'Transportation',
                'slug' => 'transportation',
            ],
            [
                'name' => 'Healthcare',
                'slug' => 'healthcare',
            ],
            [
                'name' => 'Education',
                'slug' => 'education',
            ],
        ];

        Industry::insert($industries);
    }
}

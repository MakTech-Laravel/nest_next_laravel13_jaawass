<?php

namespace Database\Seeders;

use App\Models\Industry;
use App\Models\SubCategory;
use Illuminate\Database\Seeder;

class SubCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $subCategories = [
            ['industry_slug' => 'manufacturing', 'name' => 'sub category 0', 'slug' => 'category-0'],
            ['industry_slug' => 'construction', 'name' => 'sub category 1', 'slug' => 'category-1'],
            ['industry_slug' => 'agriculture', 'name' => 'sub category 2', 'slug' => 'category-2'],
            ['industry_slug' => 'mining', 'name' => 'sub category 3', 'slug' => 'category-3'],
            ['industry_slug' => 'energy', 'name' => 'sub category 4', 'slug' => 'category-4'],
            ['industry_slug' => 'transportation', 'name' => 'sub category 5', 'slug' => 'category-5'],
            ['industry_slug' => 'healthcare', 'name' => 'sub category 6', 'slug' => 'category-6'],
            ['industry_slug' => 'education', 'name' => 'sub category 7', 'slug' => 'category-7'],
        ];

        foreach ($subCategories as $subCategory) {
            $industry = Industry::query()->where('slug', $subCategory['industry_slug'])->first();

            if ($industry === null) {
                $this->command?->warn("Industry \"{$subCategory['industry_slug']}\" not found — run IndustrySeeder first.");

                continue;
            }

            SubCategory::query()->updateOrCreate(
                ['slug' => $subCategory['slug']],
                [
                    'industry_id' => $industry->id,
                    'name' => $subCategory['name'],
                    'description' => null,
                    'icon' => null,
                    'icon_color' => null,
                    'tags' => null,
                    'sort_order' => 0,
                ],
            );
        }
    }
}

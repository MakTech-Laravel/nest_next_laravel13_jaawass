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
            ['name' => 'General', 'slug' => 'general'],
            ['name' => 'For Buyers', 'slug' => 'for-buyers'],
            ['name' => 'For Manufacturers', 'slug' => 'for-manufacturers'],
            ['name' => 'Review & Trust', 'slug' => 'review-trust'],
            ['name' => 'Messaging & Communication', 'slug' => 'messaging-communication'],
            ['name' => 'Billing & Payments', 'slug' => 'billing-payments'],
        ];

        foreach ($categories as $index => $category) {
            FaqCategory::query()->updateOrCreate(
                ['slug' => $category['slug']],
                [
                    'name' => $category['name'],
                    'sort' => $index + 1,
                ],
            );
        }

        $validSlugs = collect($categories)->pluck('slug')->all();

        FaqCategory::query()
            ->whereNotIn('slug', $validSlugs)
            ->each(function (FaqCategory $category): void {
                $category->faqs()->delete();
                $category->delete();
            });
    }
}

<?php

namespace Database\Seeders;

use App\Models\ArticleCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ArticleCategorySeeder extends Seeder
{
    /**
     * Categories used by blog articles (from /blog static content).
     *
     * @var list<string>
     */
    private array $categories = [
        'Global Sourcing',
        'RFQ Guides',
        'Buyer Guides',
        'Factory Review',
        'Supplier Comparison',
        'Private Label',
        'Manufacturer Tips',
    ];

    public function run(): void
    {
        $sourceLocale = (string) config('translation.source_locale', 'en');

        foreach ($this->categories as $name) {
            $slug = Str::slug($name);

            $category = ArticleCategory::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $name,
                    'status' => true,
                ]
            );

            $category->upsertTranslations([
                $sourceLocale => ['name' => $name],
            ]);
        }
    }
}

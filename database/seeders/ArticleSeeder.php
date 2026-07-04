<?php

namespace Database\Seeders;

use App\Enums\ArticleStatusEnum;
use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class ArticleSeeder extends Seeder
{
    public function run(): void
    {
        $articles = json_decode(
            file_get_contents(database_path('seeders/data/blog_articles.json')),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $creator = User::query()->where('email', 'admin@dev.com')->first();

        if ($creator === null) {
            $this->command?->warn('ArticleSeeder: admin@dev.com not found. Run UserSeeder first.');

            return;
        }

        $sourceLocale = (string) config('translation.source_locale', 'en');

        $categories = ArticleCategory::query()
            ->get()
            ->keyBy('slug');

        foreach ($articles as $row) {
            $categorySlug = Str::slug((string) $row['category']);
            $category = $categories->get($categorySlug);

            if ($category === null) {
                $this->command?->warn("ArticleSeeder: category [{$row['category']}] not found, skipping [{$row['slug']}]");

                continue;
            }

            $article = Article::query()->updateOrCreate(
                ['slug' => $row['slug']],
                [
                    'title' => $row['title'],
                    'excerpt' => $row['excerpt'],
                    'content' => $row['content'],
                    'content_format' => 'html',
                    'tags' => $row['tags'] ?? [],
                    'author' => $row['author'],
                    'is_featured' => (bool) ($row['is_featured'] ?? false),
                    'status' => ArticleStatusEnum::PUBLISHED,
                    'published_at' => Carbon::parse($row['published_at']),
                    'creator_id' => $creator->id,
                    'article_category_id' => $category->id,
                ]
            );

            $article->upsertTranslations([
                $sourceLocale => [
                    'title' => $row['title'],
                    'content' => $row['content'],
                    'excerpt' => $row['excerpt'],
                ],
            ]);
        }
    }
}

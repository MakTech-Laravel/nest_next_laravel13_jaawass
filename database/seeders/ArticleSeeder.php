<?php

namespace Database\Seeders;

use App\Enums\ArticleStatusEnum;
use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ArticleSeeder extends Seeder
{
    public function run(): void
    {
        $jsonPath = $this->resolveArticlesJsonPath();

        $articles = json_decode(
            file_get_contents($jsonPath),
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

            $articleImage = $this->seedArticleImage($row);

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
                    'article_image' => $articleImage,
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

    private function resolveArticlesJsonPath(): string
    {
        foreach (['seeders/Data/blog_articles.json', 'seeders/data/blog_articles.json'] as $relative) {
            $path = database_path($relative);
            if (is_file($path)) {
                return $path;
            }
        }

        throw new \RuntimeException('ArticleSeeder: blog_articles.json not found in database/seeders/Data or database/seeders/data.');
    }

    private function seedArticleImage(array $row): ?string
    {
        $filename = $row['image'] ?? null;
        if (! is_string($filename) || $filename === '') {
            return null;
        }

        $dest = 'articles/'.$filename;

        if (Storage::disk('public')->exists($dest)) {
            return $dest;
        }

        foreach ($this->imageSourceDirectories() as $dir) {
            $source = $dir.DIRECTORY_SEPARATOR.$filename;
            if (! is_file($source)) {
                continue;
            }

            Storage::disk('public')->put($dest, file_get_contents($source));

            return $dest;
        }

        $this->command?->warn("ArticleSeeder: image [{$filename}] not found for [{$row['slug']}].");

        return null;
    }

    /**
     * @return list<string>
     */
    private function imageSourceDirectories(): array
    {
        return array_values(array_filter([
            database_path('seeders/Data/images'),
            database_path('seeders/data/images'),
            base_path('../jaawaas_cli/public/blog'),
        ], is_dir(...)));
    }
}

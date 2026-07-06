<?php

namespace App\Models;

use App\Enums\ArticleStatusEnum;
use App\Jobs\TranslateModelJob;
use App\Services\LocaleTranslationResolver;
use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['title', 'slug', 'excerpt', 'content', 'content_format', 'tags', 'author', 'article_image','is_featured', 'status', 'published_at', 'archived_at', 'views', 'creator_id', 'article_category_id'])]
class Article extends Model
{
    use HasTranslations;

    protected $casts = [
        'status' => ArticleStatusEnum::class,
        'is_featured' => 'boolean',
        'tags' => 'array',
        'published_at' => 'datetime',
        'archived_at' => 'datetime',
    ];

    // Translations
    public function translationModelClass(): string
    {
        return ArticleTranslation::class;
    }

    public function translatableFields(): array
    {
        return ['title', 'content', 'excerpt'];
    }

    public function translations()
    {
        return $this->hasMany(ArticleTranslation::class, 'article_id', 'id');
    }

    public function localizedData(?string $locale = null, ?string $fallbackLocale = null): array
    {
        $fields = app(LocaleTranslationResolver::class)->fromRelation(
            $this,
            'translations',
            'locale',
            [
                'title' => 'title',
                'content' => 'content',
                'excerpt' => 'excerpt',
            ],
            ['title', 'content', 'excerpt'],
            $locale,
            $fallbackLocale
        );

        return [
            'title' => $fields['title'],
            'content' => $fields['content'],
            'excerpt' => $fields['excerpt'],
        ];
    }

    public function autoTranslate(array $sourceData, ?string $sourceLocale = null): void
    {
        if (config('translation.queue.enabled', true)) {
            TranslateModelJob::dispatch($this, $sourceData, $sourceLocale);
        } else {
            $this->dispatchTranslations($sourceData, $sourceLocale);
        }
    }

    // Relations
    public function category()
    {
        return $this->belongsTo(ArticleCategory::class, 'article_category_id', 'id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id', 'id');
    }

    public function incrementViews(): void
    {
        $this->increment('views');
    }
    
    public function getArticleImageUrlAttribute()
    {
        return storage_url($this->article_image);
    }

    protected $appends = ['article_image_url'];
}

<?php

namespace App\Models;

use App\Jobs\TranslateModelJob;
use App\Services\LocaleTranslationResolver;
use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Model;


class Faq extends Model
{
    //
    use HasTranslations;
    protected $fillable = [
        'question',
        'answer',
        'faq_category_id',
        'sort',
    ];
    // For Translations 

    protected function translationModelClass(): string
    {
        return FaqTranslation::class;
    }

    public function translatableFields(): array
    {
        return ['question', 'answer'];
    }

    public function translations()
    {
        return $this->hasMany(FaqTranslation::class, 'faq_id', 'id');
    }


    public function localizedData(?string $locale = null, ?string $fallbackLocale = null): array
    {
        $fields = app(LocaleTranslationResolver::class)->fromRelation(
            $this,
            'translations',
            'locale',
            [
                'question' => 'question',
                'answer' => 'answer',
            ],
            ['question', 'answer'],
            $locale,
            $fallbackLocale
        );

        return [
            'question' => $fields['question'],
            'answer' => $fields['answer'],
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
    // End for translations
    public function category()
    {
        return $this->belongsTo(FaqCategory::class, 'faq_category_id');
    }

    public function clicks()
    {
        return $this->hasMany(FaqClick::class);
    }

    public function getClicksCountAttribute()
    {
        return $this->clicks()->count();
    }

    protected $appends = ['clicks_count'];
}

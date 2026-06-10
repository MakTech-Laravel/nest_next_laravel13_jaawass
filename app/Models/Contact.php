<?php

namespace App\Models;

use App\Jobs\TranslateModelJob;
use App\Services\LocaleTranslationResolver;
use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'email', 'company_name', 'inquiry_type', 'message', 'is_read'])]
class Contact extends Model
{
    use HasFactory;
    use HasTranslations;

    protected function casts(): array
    {
        return [
            'is_read' => 'boolean',
        ];
    }

    protected function translationModelClass(): string
    {
        return ContactTranslation::class;
    }

    public function translatableFields(): array
    {
        return ['message'];
    }

    public function translations()
    {
        return $this->hasMany(ContactTranslation::class, 'contact_id', 'id');
    }

    public function localizedData(?string $locale = null, ?string $fallbackLocale = null): array
    {
        $fields = app(LocaleTranslationResolver::class)->fromRelation(
            $this,
            'translations',
            'locale',
            [
                'message' => 'message',
            ],
            ['message'],
            $locale,
            $fallbackLocale
        );

        return [
            'message' => $fields['message'],
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
}

<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Jobs\TranslateModelJob;
use App\Services\LocaleTranslationResolver;
use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

#[Fillable([
    'name',
    'description',
    'slug',
    'icon',
    'color',
    'title_color',
    'desc_color',
    'btn_color',
    'supplier_color',
    'sort_order',
    'featured',
    'icon_color',
])]
class Industry extends Model
{
    //

    use HasTranslations;

    protected function translationModelClass(): string
    {
        return IndustryTranslation::class;
    }

    public function translatableFields(): array
    {
        return ['name', 'description'];
    }

    public function translations()
    {
        return $this->hasMany(IndustryTranslation::class, 'industry_id', 'id');
    }

    public function localizedData(?string $locale = null, ?string $fallbackLocale = null): array
    {
        $fields = app(LocaleTranslationResolver::class)->fromRelation(
            $this,
            'translations',
            'locale',
            [
                'name' => 'name',
                'description' => 'description',
            ],
            ['name', 'description'],
            $locale,
            $fallbackLocale
        );

        return [
            'name' => $fields['name'],
            'description' => $fields['description'],
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

    public function subCategories()
    {
        return $this->hasMany(SubCategory::class, 'industry_id', 'id');
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'industry_id', 'id');
    }

    public function companies()
    {
        return $this->belongsToMany(Company::class, 'company_industry', 'industry_id', 'company_id');
    }

    /**
     * @param  Builder<Industry>  $query
     * @return Builder<Industry>
     */
    public function scopeWithSupplierCount(Builder $query): Builder
    {
        return $query->withCount([
            'products as suppliers_count' => fn (Builder $productQuery) => $productQuery
                ->select(DB::raw('count(distinct user_id)'))
                ->whereIn(
                    'user_id',
                    User::query()
                        ->select('id')
                        ->where('role', UserRole::MANUFACTURER->value),
                ),
        ]);
    }
}

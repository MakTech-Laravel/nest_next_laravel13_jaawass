<?php

namespace App\Models;

use App\Jobs\TranslateModelJob;
use App\Services\LocaleTranslationResolver;
use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['user_id', 'currency_id', 'slug', 'industry_id', 'sub_category_id', 'name', 'description', 'view_count', 'inquiry_count', 'price', 'quantity', 'image', 'status', 'is_approved', 'keywords'])]
#[Hidden(['user_id'])]
class Product extends Model
{
    use HasFactory, HasTranslations;

    protected $table = 'products';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_approved' => 'boolean',
            'keywords' => 'array',
        ];
    }

    /* --------------------------------------------------------------
    |              HasTranslations — required implementation
    | -------------------------------------------------------------- */

    protected function translationModelClass(): string
    {
        return ProductTranslation::class;
    }

    /**
     * Fields sent to Google Translate and stored in product_translations.
     *
     * @return string[]
     */
    public function translatableFields(): array
    {
        return ['name', 'description'];
    }

    /* --------------------------------------------------------------
    |                       Relationships
    |
    | translations() is provided by HasTranslations trait.
    | Eager-load as normal: Product::with('translations')
    | -------------------------------------------------------------- */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    /* --------------------------------------------------------------
    |          Existing localisation helper — unchanged
    | -------------------------------------------------------------- */

    /**
     * @return array{name: string, description: mixed}
     */
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

    /* --------------------------------------------------------------
    |               Auto-translation dispatch
    | -------------------------------------------------------------- */

    /**
     * Queue (or sync) Google Translations for this product into every
     * active language row in the `languages` table.
     *
     * Call this after create/update when translatable fields change.
     *
     * @param  array<string, string>  $sourceData  ['name' => '...', 'description' => '...']
     * @param  string|null  $sourceLocale  BCP-47 code or null for auto-detect
     */
    public function autoTranslate(array $sourceData, ?string $sourceLocale = null): void
    {
        if (config('translation.queue.enabled', true)) {
            TranslateModelJob::dispatch($this, $sourceData, $sourceLocale);
        } else {
            $this->dispatchTranslations($sourceData, $sourceLocale);
        }
    }

    // Relations

    public function images()
    {
        return $this->hasMany(ProductImage::class, 'product_id', 'id');
    }

    public function category()
    {
        return $this->belongsTo(Industry::class, 'industry_id', 'id');
    }

    public function subCategory()
    {
        return $this->belongsTo(Industry::class, 'sub_category_id', 'id');
    }

    public function pricingQuantities()
    {
        return $this->hasOne(PricingQuanity::class, 'product_id', 'id');
    }

    public function specifications()
    {
        return $this->hasMany(ProductSpecification::class, 'product_id', 'id');
    }

    public function productKeyFeatures()
    {
        return $this->hasMany(ProductKeyFeature::class, 'product_id', 'id');
    }

    public function customizationOptions()
    {
        return $this->hasMany(ProductCustomizationOption::class, 'product_id', 'id');
    }

    public function shippingPackaging()
    {
        return $this->hasOne(ShippingPackaging::class, 'product_id', 'id');
    }

    public function availableOptions()
    {
        return $this->hasOne(AvailableOption::class, 'product_id', 'id');
    }

    public function rfqSubmissions()
    {
        return $this->hasMany(RfqSubmission::class, 'product_id', 'id');
    }

    // Pivot Table
    public function shippingMethods()
    {
        return $this->belongsToMany(ShippingMethod::class);
    }

    public function savedByUsers()
    {
        return $this->belongsToMany(User::class, 'save_products', 'product_id', 'user_id')
            ->withTimestamps();
    }

    public function comparedByUsers()
    {
        return $this->belongsToMany(User::class, 'compare_products', 'product_id', 'user_id')
            ->withTimestamps();
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'product_id', 'id');
    }
}

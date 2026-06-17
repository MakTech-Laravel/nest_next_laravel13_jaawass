<?php

namespace App\Models;

use App\Jobs\TranslateModelJob;
use App\Services\Company\CompanySlugService;
use App\Services\LocaleTranslationResolver;
use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'company_name',
    'slug',
    'short_description',
    'long_description',
    'minimum_order_value',
    'company_logo',
    'company_type',
    'company_established',
    'company_size',
    'revenue',
    'country',
    'city',
    'street_address',
    'phone',
    'zip_code',
    'capabilities',
    'certifications',
    'export_markets',
    'language_spoken',
    'payments_term',
    'bussiness_license',
    'company_website',
    'notes',
    'factory_production',
    'mulitple_factories',
])]
#[Hidden(['user_id'])]
class Company extends Model
{
    protected $table = 'companies';

    use HasTranslations;

    protected static function booted(): void
    {
        static::creating(function (Company $company): void {
            if (blank($company->slug)) {
                app(CompanySlugService::class)->assignSlug($company);
            }
        });

        static::updating(function (Company $company): void {
            if ($company->isDirty('company_name')) {
                app(CompanySlugService::class)->assignSlug($company, $company->company_name);
            }
        });
    }

    /* --------------------------------------------------------------
    |                       Relationships
    | -------------------------------------------------------------- */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function getBussinessLicenseUrlAttribute(): ?string
    {
        $bussinessLicense = $this->bussiness_license;

        if (! is_string($bussinessLicense) || $bussinessLicense === '') {
            return null;
        }

        if (filter_var($bussinessLicense, FILTER_VALIDATE_URL) && str_starts_with($bussinessLicense, 'https://')) {
            return $bussinessLicense;
        }

        return storage_url($bussinessLicense);
    }

    // Translations
    public function translationModelClass(): string
    {
        return CompanyTranslation::class;
    }

    public function translatableFields(): array
    {
        return [
            'company_name',
            'company_type',
            'company_established',
            'company_size',
            'revenue',
            'country',
            'city',
            'street_address',
            'phone',
            'zip_code',
            'capabilities',
            'certifications',
            'export_markets',
            'notes',
            'short_description',
            'long_description',
        ];
    }

    public function translations()
    {
        return $this->hasMany(CompanyTranslation::class, 'company_id', 'id');
    }

    public function localizedData(?string $locale = null, ?string $fallbackLocale = null): array
    {
        $fields = app(LocaleTranslationResolver::class)->fromRelation(
            $this,
            'translations',
            'locale',
            [
                'company_name' => 'company_name',
                'company_type' => 'company_type',
                'company_established' => 'company_established',
                'company_size' => 'company_size',
                'revenue' => 'revenue',
                'country' => 'country',
                'city' => 'city',
                'street_address' => 'street_address',
                'phone' => 'phone',
                'zip_code' => 'zip_code',
                'capabilities' => 'capabilities',
                'certifications' => 'certifications',
                'export_markets' => 'export_markets',
                'notes' => 'notes',
                'short_description' => 'short_description',
                'long_description' => 'long_description',
            ],
            [
                'company_name',
                'company_type',
                'company_established',
                'company_size',
                'revenue',
                'country',
                'city',
                'street_address',
                'phone',
                'zip_code',
                'capabilities',
                'certifications',
                'export_markets',
                'notes',
                'short_description',
                'long_description',
            ],
            $locale,
            $fallbackLocale
        );

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'company_name' => $fields['company_name'] ?? $this->company_name,
            'company_type' => $fields['company_type'] ?? $this->company_type,
            'company_established' => $fields['company_established'] ?? $this->company_established,
            'company_size' => $fields['company_size'] ?? $this->company_size,
            'revenue' => $fields['revenue'] ?? $this->revenue,
            'country' => $fields['country'] ?? $this->country,
            'city' => $fields['city'] ?? $this->city,
            'street_address' => $fields['street_address'] ?? $this->street_address,
            'phone' => $fields['phone'] ?? $this->phone,
            'zip_code' => $fields['zip_code'] ?? $this->zip_code,
            'capabilities' => $fields['capabilities'] ?? $this->capabilities,
            'certifications' => $fields['certifications'] ?? $this->certifications,
            'export_markets' => $fields['export_markets'] ?? $this->export_markets,
            'bussiness_license' => $this->bussiness_license,
            'company_logo' => $this->company_logo,
            'notes' => $fields['notes'] ?? $this->notes,
            'short_description' => $fields['short_description'] ?? $this->short_description,
            'long_description' => $fields['long_description'] ?? $this->long_description,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
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

    // End for Translation

    // ------------------------------
    // Industries
    // ------------------------------

    public function industries()
    {
        return $this->belongsToMany(Industry::class);
    }
}

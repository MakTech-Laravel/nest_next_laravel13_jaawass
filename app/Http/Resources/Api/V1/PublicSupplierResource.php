<?php

namespace App\Http\Resources\Api\V1;

use App\Enums\UserManuFactureStatus;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class PublicSupplierResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $locale = $request->query('locale') ?? app()->getLocale();
        $company = $this->company;

        if ($company === null) {
            return [];
        }

        $localized = $company->localizedData($locale);
        $industry = $this->resolveIndustry($locale);
        $rating = $this->avg_rating !== null ? round((float) $this->avg_rating, 1) : 0.0;

        return [
            'id' => $this->id,
            'name' => $localized['company_name'] ?? $company->company_name,
            'slug' => $company->slug,
            'short_description' => $localized['short_description'] ?? $company->short_description,
            'location' => [
                'city' => $localized['city'] ?? $company->city,
                'country' => $localized['country'] ?? $company->country,
                'country_code' => null,
            ],
            'industry' => $industry['name'],
            'industry_slug' => $industry['slug'],
            'reviewed' => $this->manufacture_status === UserManuFactureStatus::APPROVED,
            'reviewed_level' => $this->resolveReviewedLevel(),
            'rating' => $rating,
            'review_count' => (int) ($this->review_count ?? 0),
            'product_count' => (int) ($this->public_product_count ?? $this->products?->count() ?? 0),
            'main_products' => $this->resolveMainProducts($locale),
            'certifications' => $this->resolveJsonList($localized['certifications'] ?? $company->certifications),
            'export_markets' => $this->resolveJsonList($localized['export_markets'] ?? $company->export_markets),
            'response_rate' => null,
            'response_time' => null,
            'on_time_delivery' => null,
        ];
    }

    /**
     * @return array{name: ?string, slug: ?string}
     */
    private function resolveIndustry(string $locale): array
    {
        $industries = $this->company?->industries;

        if ($industries !== null && $industries->isNotEmpty()) {
            $primary = $industries->first();

            return [
                'name' => $primary->name,
                'slug' => $primary->slug,
            ];
        }

        $product = $this->products?->first()?->category;

        if ($product !== null) {
            return [
                'name' => $product->name,
                'slug' => $product->slug,
            ];
        }

        return ['name' => null, 'slug' => null];
    }

    /**
     * @return array<int, string>
     */
    private function resolveMainProducts(string $locale): array
    {
        if (! $this->relationLoaded('products')) {
            return [];
        }

        return $this->products
            ->map(function ($product) use ($locale): string {
                $localized = $product->localizedData($locale);

                return (string) ($localized['name'] ?? $product->name);
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function resolveJsonList(mixed $value): array
    {
        if (is_array($value)) {
            return array_values(array_filter($value, fn ($item) => is_string($item) && $item !== ''));
        }

        if (! is_string($value) || $value === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        if (! is_array($decoded)) {
            return [];
        }

        return array_values(array_filter($decoded, fn ($item) => is_string($item) && $item !== ''));
    }

    private function resolveReviewedLevel(): string
    {
        $subscription = $this->subscription;

        if ($subscription?->relationLoaded('plan') && $subscription->plan !== null) {
            foreach ($subscription->plan->planFeatures ?? [] as $planFeature) {
                if ($planFeature->feature?->key === 'featured_supplier_badge') {
                    return 'featured';
                }
            }
        }

        return 'standard';
    }
}

<?php

namespace App\Http\Resources\Api\V1;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class PublicSupplierDetailResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $card = (new PublicSupplierResource($this->resource))->toArray($request);

        return array_merge($card, [
            'long_description' => $this->resolveLongDescription($request),
            'logo' => storage_url($this->company?->company_logo),
            'year_established' => $this->company?->company_established,
            'employee_count' => $this->company?->company_size,
            'revenue' => $this->company?->revenue,
            'min_order_value' => $this->company?->minimum_order_value,
            'business_type' => $this->resolveLocalizedField($request, 'company_type'),
            'capabilities' => $this->resolveJsonField($request, 'capabilities'),
            'languages' => $this->resolveJsonField($request, 'language_spoken'),
            'payment_terms' => $this->resolveJsonField($request, 'payments_term'),
            'website' => $this->company?->company_website,
            'factory_photos' => $this->whenLoaded('factoryImages', fn () => $this->factoryImages
                ->map(fn ($image) => $image->url ?? storage_url($image->path))
                ->values()
                ->all()),
            'industries' => $this->whenLoaded('company', fn () => $this->company?->industries
                ?->map(fn ($industry) => [
                    'id' => $industry->id,
                    'name' => $industry->name,
                    'slug' => $industry->slug,
                ])
                ->values()
                ->all() ?? []),
            'company' => $this->whenLoaded('company', fn () => $this->company === null
                ? null
                : new UserInformationResource($this->company)),
        ]);
    }

    private function resolveLongDescription(Request $request): ?string
    {
        $locale = $request->query('locale') ?? app()->getLocale();
        $company = $this->company;

        if ($company === null) {
            return null;
        }

        $localized = $company->localizedData($locale);

        return $localized['long_description'] ?? $company->long_description;
    }

    private function resolveLocalizedField(Request $request, string $field): mixed
    {
        $locale = $request->query('locale') ?? app()->getLocale();
        $company = $this->company;

        if ($company === null) {
            return null;
        }

        $localized = $company->localizedData($locale);

        return $localized[$field] ?? $company->{$field};
    }

    /**
     * @return array<int, mixed>|null
     */
    private function resolveJsonField(Request $request, string $field): ?array
    {
        $value = $this->company?->{$field};

        if (is_array($value)) {
            return $value;
        }

        if (! is_string($value) || $value === '') {
            return null;
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : null;
    }
}

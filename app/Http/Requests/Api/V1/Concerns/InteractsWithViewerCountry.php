<?php

namespace App\Http\Requests\Api\V1\Concerns;

use App\Support\Countries\CountryCatalog;
use App\Support\Countries\ViewerCountryResolver;

trait InteractsWithViewerCountry
{
    public function viewerCountryCode(): ?string
    {
        return app(ViewerCountryResolver::class)->resolveFromRequest($this);
    }

    public function supplierLocationCountry(): ?string
    {
        $country = $this->input('country');

        if (! is_string($country) || trim($country) === '') {
            return null;
        }

        $country = trim($country);

        if (preg_match('/^[A-Za-z]{2}$/', $country) === 1) {
            return null;
        }

        return $country;
    }

    public function resolvesCountryAsViewerMarket(): bool
    {
        $country = $this->input('country');

        return is_string($country) && preg_match('/^[A-Za-z]{2}$/', trim($country)) === 1;
    }
}

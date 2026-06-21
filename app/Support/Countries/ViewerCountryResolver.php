<?php

namespace App\Support\Countries;

use Illuminate\Http\Request;

class ViewerCountryResolver
{
    public function resolveFromRequest(Request $request): ?string
    {
        $explicit = $request->input('viewer_country_code');

        if (is_string($explicit) && $explicit !== '') {
            return CountryCatalog::normalizeCode($explicit);
        }

        $countryParam = $request->input('country');

        if (is_string($countryParam) && preg_match('/^[A-Za-z]{2}$/', trim($countryParam)) === 1) {
            return CountryCatalog::normalizeCode($countryParam);
        }

        $header = $request->header('X-Viewer-Country-Code');

        if (is_string($header) && trim($header) !== '') {
            return CountryCatalog::normalizeCode($header);
        }

        $viewer = $request->user();

        if ($viewer !== null) {
            $viewer->loadMissing('company');

            if ($viewer->company !== null && is_string($viewer->company->country) && $viewer->company->country !== '') {
                return CountryCatalog::resolveCode($viewer->company->country);
            }
        }

        return null;
    }
}

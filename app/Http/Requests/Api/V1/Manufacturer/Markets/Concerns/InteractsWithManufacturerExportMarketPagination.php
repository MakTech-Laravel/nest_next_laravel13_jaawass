<?php

namespace App\Http\Requests\Api\V1\Manufacturer\Markets\Concerns;

trait InteractsWithManufacturerExportMarketPagination
{
    public function perPage(): int
    {
        return $this->integer('per_page', 24);
    }

    public function pageNumber(): int
    {
        return $this->integer('page', 1);
    }

    public function searchTerm(): ?string
    {
        $search = $this->input('search');

        if (! is_string($search)) {
            return null;
        }

        $search = trim($search);

        return $search === '' ? null : $search;
    }

    public function geographicRegionFilter(): ?string
    {
        $region = $this->input('geographic_region');

        if (! is_string($region)) {
            return null;
        }

        $region = trim($region);

        return $region === '' ? null : $region;
    }
}

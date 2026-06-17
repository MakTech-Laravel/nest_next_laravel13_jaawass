<?php

namespace App\Services\Company;

use App\Models\Company;
use Illuminate\Support\Str;

class CompanySlugService
{
    public function generateUniqueSlug(string $companyName, ?int $ignoreCompanyId = null): string
    {
        $baseSlug = Str::slug($companyName);

        if ($baseSlug === '') {
            $baseSlug = 'supplier';
        }

        $slug = $baseSlug;
        $suffix = 2;

        while ($this->slugExists($slug, $ignoreCompanyId)) {
            $slug = $baseSlug.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }

    public function syncSlug(Company $company, ?string $companyName = null): void
    {
        $name = $companyName ?? $company->company_name;

        if (! is_string($name) || trim($name) === '') {
            return;
        }

        $slug = $this->generateUniqueSlug($name, $company->id);

        if ($company->slug !== $slug) {
            $company->forceFill(['slug' => $slug])->save();
        }
    }

    private function slugExists(string $slug, ?int $ignoreCompanyId = null): bool
    {
        return Company::query()
            ->when($ignoreCompanyId !== null, fn ($query) => $query->where('id', '!=', $ignoreCompanyId))
            ->where('slug', $slug)
            ->exists();
    }
}

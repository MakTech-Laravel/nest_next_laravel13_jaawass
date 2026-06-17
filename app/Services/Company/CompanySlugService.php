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

    public function assignSlug(Company $company, ?string $companyName = null): void
    {
        $name = $companyName;

        if ($name === null || trim($name) === '') {
            $name = $this->resolveSlugSource($company);
        }

        if ($name === null || trim($name) === '') {
            return;
        }

        $company->slug = $this->generateUniqueSlug($name, $company->exists ? $company->id : null);
    }

    public function syncSlug(Company $company, ?string $companyName = null): void
    {
        $this->assignSlug($company, $companyName);

        if (! $company->isDirty('slug')) {
            return;
        }

        $company->save();
    }

    private function resolveSlugSource(Company $company): ?string
    {
        if (is_string($company->company_name) && trim($company->company_name) !== '') {
            return trim($company->company_name);
        }

        if ($company->relationLoaded('user') || $company->user_id !== null) {
            $user = $company->user;

            if ($user !== null) {
                $fromUser = trim("{$user->first_name} {$user->last_name}");

                if ($fromUser !== '') {
                    return $fromUser;
                }

                return 'supplier-'.$user->id;
            }
        }

        return null;
    }

    private function slugExists(string $slug, ?int $ignoreCompanyId = null): bool
    {
        return Company::query()
            ->when($ignoreCompanyId !== null, fn ($query) => $query->where('id', '!=', $ignoreCompanyId))
            ->where('slug', $slug)
            ->exists();
    }
}

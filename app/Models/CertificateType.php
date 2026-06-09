<?php

namespace App\Models;

use App\Enums\CertificateTypeStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;


#[Fillable(['name', 'slug', 'status'])]
class CertificateType extends Model
{

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', CertificateTypeStatus::ACTIVE->value);
    }
    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('status', CertificateTypeStatus::INACTIVE->value);
    }

}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;

#[Fillable([
    'copmany_id',
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
    'locale'
])]
#[Hidden(['company_id', 'id'])]
class CompanyTranslation extends Model
{
    //

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id ');
    }

    public function language()
    {
        return $this->belongsTo(Language::class, 'locale', 'locale');
    }

}


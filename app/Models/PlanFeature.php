<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Table;

#[Table('plan_feature')]
#[Fillable(['plan_id', 'feature_id', 'input_type', 'value', 'label'])]

class PlanFeature extends Model
{
    public function displayLabel(?string $locale = null): string
    {
        if (filled($this->label)) {
            return $this->label;
        }

        $this->loadMissing('feature');

        return $this->feature?->localizeData($locale)['name'] ?? '';
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function feature()
    {
        return $this->belongsTo(Feature::class);
    }
}

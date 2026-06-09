<?php

namespace App\Models;

use App\Models\Feature;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['name', 'locale', 'feature_id'])]
class FeatureTransaltion extends Model
{
    //

      public function feature()
    {
        return $this->belongsTo(Feature::class, 'feature_id', 'id');
    }

    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class, 'locale', 'locale');
    }
}

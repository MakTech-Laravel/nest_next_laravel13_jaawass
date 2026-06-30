<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['platform', 'icon', 'url', 'enabled', 'sort'])]
class SocialMediaLink extends Model
{
    protected $casts = [
        'enabled' => 'boolean',
        'sort' => 'integer',
    ];
}

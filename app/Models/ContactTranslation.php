<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['contact_id', 'locale', 'message'])]

class ContactTranslation extends Model
{
    //

    public function contact()
    {
        return $this->belongsTo(Contact::class, 'contact_id', 'id');
    }

    public function language()
    {
        return $this->belongsTo(Language::class, 'locale', 'locale');
    }
}

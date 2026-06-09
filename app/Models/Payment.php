<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
#[Fillable(['payment_id', 'payment_method', 'amount', 'status', 'user_id', 'source_id', 'source_type'])]
class Payment extends Model
{
    
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function source()
    {
        return $this->morphTo();
    }
 
}

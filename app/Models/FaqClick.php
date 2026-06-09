<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FaqClick extends Model
{
    //
    protected $fillable = [
        'faq_id',
        'user_id',
        'ip_address',
        'user_agent',
    ];
    
    public function faq()
    {
        return $this->belongsTo(Faq::class);
    }
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

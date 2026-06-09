<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    protected $fillable = [
        'manufacturer_id',
        'plan_id',
        'billing_interval',
        'status',
        'starts_at',
        'ends_at',
        'trial_ends_at',
        'auto_renew',
    ];
    
    public function manufacturer()
    {
        return $this->belongsTo(User::class, 'manufacturer_id');
    }
    
    public function plan()
    {
        return $this->belongsTo(Plan::class, 'plan_id');
    }
}

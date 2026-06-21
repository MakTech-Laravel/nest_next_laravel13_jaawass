<?php

namespace App\Models;

use App\Enums\Api\V1\SubscriptionSource;
use App\Enums\Api\V1\SubscriptionStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'expiry_reminder_sent_at',
        'source',
        'promotion_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => SubscriptionStatus::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'trial_ends_at' => 'datetime',
            'auto_renew' => 'boolean',
            'expiry_reminder_sent_at' => 'datetime',
            'source' => SubscriptionSource::class,
        ];
    }

    public function isEntitlementActive(): bool
    {
        $status = $this->status instanceof SubscriptionStatus
            ? $this->status->value
            : (string) $this->status;

        if (! in_array($status, [
            SubscriptionStatus::ACTIVE->value,
            SubscriptionStatus::TRIALING->value,
        ], true)) {
            return false;
        }

        if ($this->ends_at !== null && $this->ends_at->isPast()) {
            return false;
        }

        return true;
    }

    public function manufacturer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manufacturer_id');
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'plan_id');
    }

    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class, 'promotion_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'subscription_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(SubscriptionLog::class, 'manufacturer_id', 'manufacturer_id');
    }

    public function scopeEntitlementActive(Builder $query): Builder
    {
        return $query
            ->whereIn('status', [
                SubscriptionStatus::ACTIVE->value,
                SubscriptionStatus::TRIALING->value,
            ])
            ->where(function (Builder $builder): void {
                $builder
                    ->whereNull('ends_at')
                    ->orWhere('ends_at', '>', now());
            });
    }
}

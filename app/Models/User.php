<?php

namespace App\Models;

use App\Enums\UserManuFactureStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Passport\Contracts\OAuthenticatable;
use Laravel\Passport\HasApiTokens;

#[Fillable([
    'first_name',
    'last_name',
    'email',
    'password',
    'role',
    'agreed_to_terms',
    'manufacture_status',
    'manufacture_status_reason',
    'manufacture_status_at',
    'status',
    'deactivated_at',
    'deactivated_reason',
    'deleted_at',
    'deleted_reason',
    'is_permanently_deleted',
    // Social login fields
    'google_id',
    'facebook_id',
    'avatar',
    'email_verified_at',
    'preferred_currency_id',
    'preferred_language',
    'timezone',
    'quote_notification',
    'message_notification',
    'supplier_update',
    'weekly_digest',
    'marketing_promotion',
])]
#[Hidden(['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'])]
class User extends Authenticatable implements OAuthenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    use TwoFactorAuthenticatable;

    protected static function booted(): void
    {
        static::creating(function (User $user): void {
            if ($user->preferred_language === null || $user->preferred_language === '') {
                $user->preferred_language = 'en';
            }

            if ($user->preferred_currency_id === null) {
                $user->preferred_currency_id = static::defaultPreferredCurrencyId();
            }
        });
    }

    protected static function defaultPreferredCurrencyId(): ?int
    {
        try {
            return Currency::query()->where('code', 'USD')->value('id');
        } catch (\Throwable) {
            return null;
        }
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'status' => UserStatus::class,
            'agreed_to_terms' => 'boolean',
            'manufacture_status' => UserManuFactureStatus::class,
            'manufacture_status_at' => 'datetime',
            'deactivated_at' => 'datetime',
            'deleted_at' => 'datetime',
            'is_permanently_deleted' => 'boolean',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    protected $appends = ['avatar_url'];

    /* --------------------------------------------------------------
    |                       Relationships
    | -------------------------------------------------------------- */
    public function preferredCurrency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'preferred_currency_id');
    }

    public function company(): HasOne
    {
        return $this->hasOne(Company::class, 'user_id', 'id');
    }

    public function factoryImages(): HasMany
    {
        return $this->hasMany(UserFactoryImage::class, 'user_id', 'id')->orderBy('created_at', 'desc');
    }

    public function additionalInformationRequests(): HasMany
    {
        return $this->hasMany(ManufacturerAdditionalInformationRequest::class, 'user_id', 'id')->latest();
    }

    public function requestedAdditionalInformationRequests(): HasMany
    {
        return $this->hasMany(ManufacturerAdditionalInformationRequest::class, 'requested_by', 'id')->latest();
    }

    public function loginHistories(): HasMany
    {
        return $this->hasMany(UserLoginHistory::class, 'user_id', 'id')->orderByDesc('logged_in_at');
    }

    /**
     * @return HasMany<UserNotification, $this>
     */
    public function userNotifications(): HasMany
    {
        return $this->hasMany(UserNotification::class, 'user_id', 'id')->orderByDesc('created_at');
    }

    /**
     * Notifications this user originated (non-system senders).
     *
     * @return HasMany<UserNotification, $this>
     */
    public function sentUserNotifications(): HasMany
    {
        return $this->hasMany(UserNotification::class, 'sender_id', 'id')->orderByDesc('created_at');
    }

    /**
     * Conversations this user participates in (buyer, manufacturer, or admin).
     *
     * @return BelongsToMany<Conversation, $this>
     */
    public function conversations(): BelongsToMany
    {
        return $this
            ->belongsToMany(Conversation::class, 'conversation_participants')
            ->withTimestamps();
    }

    /* --------------------------------------------------------------
    |                       Helper Methods
    | -------------------------------------------------------------- */
    public function deletionGraceEndsAt(): ?Carbon
    {
        if ($this->deleted_at === null) {
            return null;
        }

        return $this->deleted_at->copy()->addDays(config('account.deletion_grace_days'))->endOfDay();
    }

    public function isWithinDeletionGracePeriod(): bool
    {
        if ($this->deleted_at === null) {
            return false;
        }

        return now()->lt($this->deletionGraceEndsAt());
    }

    public function isAwaitingPermanentDeletionPurge(): bool
    {
        if ($this->deleted_at === null) {
            return false;
        }

        return now()->gte($this->deletionGraceEndsAt());
    }

    /**
     * Social-only accounts have no password set.
     */
    public function hasSocialLoginOnly(): bool
    {
        return $this->password === null;
    }

    /* --------------------------------------------------------------
    |         Accessors, Mutators, Helpers, Scopes & Attributes
    | -------------------------------------------------------------- */
    public function created_at_formatted(): string
    {
        return $this->created_at->format('Y-m-d H:i:s');
    }

    public function updated_at_formatted(): ?string
    {
        return $this->created_at->eq($this->updated_at) ? null : $this->updated_at->format('Y-m-d H:i:s');
    }

    public function isSocialOnlyAccount(): bool
    {
        return $this->password === null;
    }

    public function getAvatarUrlAttribute(): ?string
    {
        if (! is_string($this->avatar) || $this->avatar === '' || $this->avatar === null) {
            return null;
        }

        if (filter_var($this->avatar, FILTER_VALIDATE_URL) && str_starts_with($this->avatar, 'https://')) {
            return $this->avatar;
        }

        return storage_url($this->avatar);
    }

    /**
     * Compute `users.status` after an admin changes manufacturer approval (excludes manual suspend).
     */
    public function resolvedStatusAfterManufactureReview(UserManuFactureStatus $incoming): UserStatus
    {
        if ($incoming === UserManuFactureStatus::REJECTED) {
            return UserStatus::SUSPENDED;
        }

        if ($this->deleted_at !== null) {
            return UserStatus::SCHEDULED_DELETION;
        }

        if ($this->deactivated_at !== null) {
            return UserStatus::DEACTIVATED;
        }

        if ($incoming === UserManuFactureStatus::PENDING) {
            return UserStatus::PENDING;
        }

        return UserStatus::ACTIVE;
    }

    /**
     * Compute `users.status` from deletion, deactivation, and manufacture state (not for arbitrary suspend).
     */
    public function resolvedStatusForActiveAccountState(): UserStatus
    {
        if ($this->deleted_at !== null) {
            return UserStatus::SCHEDULED_DELETION;
        }

        if ($this->deactivated_at !== null) {
            return UserStatus::DEACTIVATED;
        }

        if ($this->role->isManufacturer()) {
            $mfg = UserManuFactureStatus::normalizedForManufacturer($this->manufacture_status);

            return match ($mfg) {
                UserManuFactureStatus::PENDING => UserStatus::PENDING,
                UserManuFactureStatus::REJECTED => UserStatus::SUSPENDED,
                default => UserStatus::ACTIVE,
            };
        }

        return UserStatus::ACTIVE;
    }

    public function clicks()
    {
        return $this->hasMany(FaqClick::class);
    }

    public function subscription()
    {
        return $this->hasOne(Subscription::class, 'manufacturer_id');
    }

    public function subscriptionLogs()
    {
        return $this->hasMany(SubscriptionLog::class, 'manufacturer_id');
    }

    // For Review

    public function manufacturerReviews()
    {
        return $this->hasMany(Review::class, 'user_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function catalogs(): HasMany
    {
        return $this->hasMany(Catalog::class);
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }

    public function reviewerReviews()
    {
        return $this->hasMany(Review::class, 'reviewer_id');
    }

    public function scopeIsAdmin(Builder $query): Builder
    {
        return $query->where('role', UserRole::ADMIN->value);
    }

    public function scopeIsManufacturer(Builder $query): Builder
    {
        return $query->where('role', UserRole::MANUFACTURER->value);
    }

    public function scopeIsBuyer(Builder $query): Builder
    {
        return $query->where('role', UserRole::BUYER->value);
    }

    // Promotions

    public function promotions(): BelongsToMany
    {
        return $this->belongsToMany(Promotion::class, 'promotion_user', 'user_id', 'promotion_id')
            ->withPivot('status', 'participated_at', 'trial_ends_at')
            ->withTimestamps();
    }

    /**
     * @return BelongsToMany<Product, $this>
     */
    public function savedProducts(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'save_products', 'user_id', 'product_id')
            ->withTimestamps();
    }

    /**
     * @return BelongsToMany<Product, $this>
     */
    public function compareProducts(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'compare_products', 'user_id', 'product_id')
            ->withTimestamps();
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function savedSuppliers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'save_suppliers', 'user_id', 'supplier_id')
            ->withTimestamps();
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function compareSuppliers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'compare_suppliers', 'user_id', 'supplier_id')
            ->withTimestamps();
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function savedByBuyers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'save_suppliers', 'supplier_id', 'user_id')
            ->withTimestamps();
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function comparedByBuyers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'compare_suppliers', 'supplier_id', 'user_id')
            ->withTimestamps();
    }
}

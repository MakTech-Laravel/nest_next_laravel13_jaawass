<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['name', 'created_by'])]
class Conversation extends Model
{
    /** @use HasFactory<ConversationFactory> */
    use HasFactory;

    /**
     * @return HasMany<Message, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    /**
     * @return HasOne<Message, $this>
     */
    public function latestMessage(): HasOne
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, ConversationParticipant::class)
            ->withTimestamps();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<ConversationActivityLog, $this>
     */
    public function activityLogs(): HasMany
    {
        return $this->hasMany(ConversationActivityLog::class)->orderByDesc('id');
    }

    public function hasParticipant(User $user): bool
    {
        return $this->participants()
            ->where('users.id', $user->id)
            ->exists();
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeForParticipant(Builder $query, User $user): Builder
    {
        return $query->whereHas('participants', function (Builder $q) use ($user): void {
            $q->where('users.id', $user->id);
        });
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeBuyerManufacturerOnly(Builder $query): Builder
    {
        return $query
            ->whereHas('participants', function (Builder $q): void {
                $q->where('role', UserRole::BUYER->value);
            })
            ->whereHas('participants', function (Builder $q): void {
                $q->where('role', UserRole::MANUFACTURER->value);
            })
            ->whereDoesntHave('participants', function (Builder $q): void {
                $q->where('role', UserRole::ADMIN->value);
            });
    }

    public function isBuyerManufacturerChat(): bool
    {
        $roles = $this->participants()
            ->pluck('role')
            ->map(static fn (UserRole|string $role): string => $role instanceof UserRole ? $role->value : (string) $role);

        return $roles->contains(UserRole::BUYER->value)
            && $roles->contains(UserRole::MANUFACTURER->value)
            && ! $roles->contains(UserRole::ADMIN->value);
    }
}

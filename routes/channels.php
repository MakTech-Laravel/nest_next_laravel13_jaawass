<?php

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('user.{userId}', function (User $user, string $userId): bool {
    return (int) $user->getAuthIdentifier() === (int) $userId;
}, ['guards' => ['api']]);

Broadcast::channel('chat.room.{conversationId}', function (User $user, string $conversationId): bool {
    return Conversation::query()
        ->whereKey($conversationId)
        ->whereHas('participants', function ($query) use ($user): void {
            $query->where('users.id', $user->id);
        })
        ->exists();
}, ['guards' => ['api']]);

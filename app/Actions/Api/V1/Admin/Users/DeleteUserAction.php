<?php

namespace App\Actions\Api\V1\Admin\Users;

use App\Enums\UserStatus;
use App\Models\User;

class DeleteUserAction
{
    public function handle(User $user, string $reason): User
    {
        $user->update([
            'deleted_at' => now(),
            'deleted_reason' => $reason,
            'status' => UserStatus::ScheduledDeletion,
            'is_permanently_deleted' => false,
        ]);

        // Invalidate all active sessions immediately
        $user->tokens()->update(['revoked' => true]);

        return $user->refresh();
    }
}

<?php

namespace App\Actions\Api\V1\Admin\Users;

use App\Enums\UserStatus;
use App\Models\User;

class DeactivateUserAction
{
    public function handle(User $user, string $reason): User
    {
        $user->update([
            'deactivated_at' => now(),
            'deactivated_reason' => $reason,
            'status' => UserStatus::DEACTIVATED,
        ]);

        // Invalidate all active sessions immediately
        $user->tokens()->update(['revoked' => true]);

        return $user->refresh();
    }
}

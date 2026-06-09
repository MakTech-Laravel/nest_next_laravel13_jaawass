<?php

namespace App\Actions\Api\V1\Admin\Users;

use App\Enums\UserStatus;
use App\Models\User;

class SuspendUserAction
{
    public function handle(User $user): User
    {
        $user->update([
            'status' => UserStatus::SUSPENDED,
        ]);

        $user->tokens()->update(['revoked' => true]);

        return $user->refresh();
    }
}

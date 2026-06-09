<?php

namespace App\Actions\Api\V1\Admin\Users;

use App\Models\User;

class ReactivateUserAction
{
    public function handle(User $user): User
    {
        $user->update([
            'deactivated_at' => null,
            'deactivated_reason' => null,
        ]);

        $user->refresh();

        $user->update([
            'status' => $user->resolvedStatusForActiveAccountState(),
        ]);

        return $user->refresh();
    }
}

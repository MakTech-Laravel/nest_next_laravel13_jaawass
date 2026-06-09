<?php

namespace App\Actions\Api\V1\Admin\Users;

use App\Models\User;

class UnsuspendUserAction
{
    public function handle(User $user): User
    {
        $user->update([
            'status' => $user->resolvedStatusForActiveAccountState(),
        ]);

        return $user->refresh();
    }
}

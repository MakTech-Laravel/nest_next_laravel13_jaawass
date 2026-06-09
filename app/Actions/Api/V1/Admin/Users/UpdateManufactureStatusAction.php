<?php

namespace App\Actions\Api\V1\Admin\Users;

use App\Enums\UserManuFactureStatus;
use App\Models\User;

class UpdateManufactureStatusAction
{
    public function handle(User $user, UserManuFactureStatus $status, ?string $reason): User
    {
        $user->update([
            'manufacture_status' => $status,
            'manufacture_status_reason' => $status->isRejected() ? $reason : null,
            'manufacture_status_at' => now(),
            'status' => $user->resolvedStatusAfterManufactureReview($status),
        ]);

        return $user->refresh()->load(['company', 'factoryImages']);
    }
}

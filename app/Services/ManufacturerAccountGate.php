<?php

namespace App\Services;

use App\Enums\UserManuFactureStatus;
use App\Models\User;

class ManufacturerAccountGate
{
    /**
     * @return array{allowed: true}|array{allowed: false, message: string, rejection_reason: ?string}
     */
    public function evaluateLogin(User $user): array
    {
        if (! $user->role->isManufacturer()) {
            return ['allowed' => true];
        }

        $status = UserManuFactureStatus::normalizedForManufacturer($user->manufacture_status);

        if ($status->isRejected()) {
            return [
                'allowed' => false,
                'message' => __('auth.manufacturer.rejected'),
                'rejection_reason' => $user->manufacture_status_reason,
            ];
        }

        // Pending and approved manufacturers may log in.
        // Pending accounts are limited to review routes via EnsureApprovedManufacturer middleware.
        return ['allowed' => true];
    }
}

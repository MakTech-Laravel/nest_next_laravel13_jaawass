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

        if ($status->allowsApiLogin()) {
            return ['allowed' => true];
        }

        if ($status->isRejected()) {
            return [
                'allowed' => false,
                'message' => __('auth.manufacturer.rejected'),
                'rejection_reason' => $user->manufacture_status_reason,
            ];
        }

        return [
            'allowed' => false,
            'message' => __('auth.manufacturer.login-pending'),
            'rejection_reason' => null,
        ];
    }
}

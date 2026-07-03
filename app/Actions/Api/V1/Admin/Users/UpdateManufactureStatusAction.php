<?php

namespace App\Actions\Api\V1\Admin\Users;

use App\Enums\UserManuFactureStatus;
use App\Models\User;
use App\Services\Manufacturer\ManufacturerStatusNotificationService;

class UpdateManufactureStatusAction
{
    public function __construct(
        private readonly ManufacturerStatusNotificationService $statusNotificationService,
    ) {}

    public function handle(User $user, UserManuFactureStatus $status, ?string $reason): User
    {
        $user->update([
            'manufacture_status' => $status,
            'manufacture_status_reason' => $status->isRejected() ? $reason : null,
            'manufacture_status_at' => now(),
            'status' => $user->resolvedStatusAfterManufactureReview($status),
        ]);

        $fresh = $user->refresh()->load(['company', 'factoryImages']);

        if ($status === UserManuFactureStatus::APPROVED || $status->isRejected()) {
            $this->statusNotificationService->notifyStatusChanged($fresh, $status, $reason);
        }

        return $fresh;
    }
}

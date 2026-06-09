<?php

namespace App\Actions\Api\V1\Auth;

use App\Models\User;
use App\Models\UserLoginHistory;
use Illuminate\Http\Request;

class RecordLoginHistoryAction
{
    public function handle(User $user, Request $request, ?string $deviceName = null): void
    {
        UserLoginHistory::query()->create([
            'user_id' => $user->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'device_name' => $deviceName,
            'logged_in_at' => now(),
        ]);
    }
}

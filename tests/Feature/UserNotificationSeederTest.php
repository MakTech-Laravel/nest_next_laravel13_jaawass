<?php

use App\Models\User;
use App\Models\UserNotification;
use Database\Seeders\UserNotificationSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('user notification seeder creates fifteen notifications per seeded user', function () {
    $this->seed(UserSeeder::class);
    $this->seed(UserNotificationSeeder::class);

    $userCount = User::query()->count();

    expect($userCount)->toBeGreaterThan(0)
        ->and(UserNotification::query()->count())->toBe($userCount * 15);
});

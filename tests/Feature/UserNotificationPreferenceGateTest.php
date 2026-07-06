<?php

use App\Models\User;
use App\Support\Notifications\UserNotificationPreferenceGate;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('transactional notifications always allow delivery', function (): void {
    $user = User::factory()->create([
        'quote_notification' => false,
        'message_notification' => false,
    ]);

    expect(UserNotificationPreferenceGate::allowsEmail($user, 'order.created'))->toBeTrue()
        ->and(UserNotificationPreferenceGate::allowsInApp($user, 'support.ticket.created'))->toBeTrue()
        ->and(UserNotificationPreferenceGate::allowsEmail($user, 'supplier.report.received'))->toBeTrue();
});

test('marketing notifications require opt in', function (): void {
    config(['notifications.enforce_preferences' => true]);

    $user = User::factory()->create(['marketing_promotion' => false]);

    expect(UserNotificationPreferenceGate::allowsEmail($user, 'marketing.promo'))->toBeFalse();

    $user->update(['marketing_promotion' => true]);

    expect(UserNotificationPreferenceGate::allowsEmail($user->fresh(), 'marketing.promo'))->toBeTrue();
});

test('optional channels default to enabled for backward compatibility', function (): void {
    config([
        'notifications.enforce_preferences' => true,
        'notifications.optional_channels_default_enabled' => true,
    ]);

    $user = User::factory()->create([
        'message_notification' => false,
        'quote_notification' => false,
    ]);

    expect(UserNotificationPreferenceGate::allowsEmail($user, 'conversation.message'))->toBeTrue()
        ->and(UserNotificationPreferenceGate::allowsInApp($user, 'rfq.created'))->toBeTrue();
});

test('optional channels can be disabled when user opts in then off', function (): void {
    config([
        'notifications.enforce_preferences' => true,
        'notifications.optional_channels_default_enabled' => false,
    ]);

    $user = User::factory()->create([
        'message_notification' => false,
        'quote_notification' => false,
    ]);

    expect(UserNotificationPreferenceGate::allowsEmail($user, 'conversation.message'))->toBeFalse();

    $user->update(['message_notification' => true]);

    expect(UserNotificationPreferenceGate::allowsEmail($user->fresh(), 'conversation.message'))->toBeTrue();
});

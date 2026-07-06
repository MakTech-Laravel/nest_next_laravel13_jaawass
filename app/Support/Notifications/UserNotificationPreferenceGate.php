<?php

namespace App\Support\Notifications;

use App\Models\User;

final class UserNotificationPreferenceGate
{
    public static function allowsEmail(User $user, string $notificationType): bool
    {
        return self::allowsChannel($user, $notificationType, 'email');
    }

    public static function allowsInApp(User $user, string $notificationType): bool
    {
        return self::allowsChannel($user, $notificationType, 'in_app');
    }

    public static function isTransactional(string $notificationType): bool
    {
        $type = strtolower(trim($notificationType));

        if ($type === '') {
            return true;
        }

        foreach ([
            'order.',
            'support.',
            'plan.subscription.',
            'manufacturer.approved',
            'manufacturer.rejected',
            'manufacturer.registered',
            'supplier.report.',
        ] as $prefix) {
            if (str_starts_with($type, $prefix)) {
                return true;
            }
        }

        return in_array($type, [
            'support.ticket.created.admin',
        ], true);
    }

    private static function allowsChannel(User $user, string $notificationType, string $channel): bool
    {
        if (! config('notifications.enforce_preferences', true)) {
            return true;
        }

        $type = strtolower(trim($notificationType));

        if (self::isTransactional($type)) {
            return true;
        }

        if (str_starts_with($type, 'marketing.') || str_starts_with($type, 'digest.')) {
            return $channel === 'email' && (bool) $user->marketing_promotion;
        }

        if (str_starts_with($type, 'weekly.') || $type === 'digest.weekly') {
            return $channel === 'email' && (bool) $user->weekly_digest;
        }

        $optionalDefault = (bool) config('notifications.optional_channels_default_enabled', true);

        if (str_starts_with($type, 'conversation.') || $type === 'conversation.message') {
            return self::optionalFlag((bool) $user->message_notification, $optionalDefault);
        }

        if (str_starts_with($type, 'rfq.')) {
            return self::optionalFlag((bool) $user->quote_notification, $optionalDefault);
        }

        if (str_starts_with($type, 'supplier.') && ! str_starts_with($type, 'supplier.report.')) {
            return self::optionalFlag((bool) $user->supplier_update, $optionalDefault);
        }

        return true;
    }

    private static function optionalFlag(bool $stored, bool $defaultEnabled): bool
    {
        if ($stored) {
            return true;
        }

        return $defaultEnabled;
    }
}

<?php

namespace App\Support\Mail;

use App\Models\User;
use App\Support\Notifications\UserNotificationPreferenceGate;
use Illuminate\Support\Collection;

final class MailNotificationHelper
{
    public static function frontendUrl(string $path = ''): string
    {
        $base = rtrim((string) config('app.frontend_url', config('app.url')), '/');
        $path = ltrim($path, '/');

        return $path === '' ? $base : $base.'/'.$path;
    }

    public static function displayName(?User $user): string
    {
        if ($user === null) {
            return 'there';
        }

        $name = trim($user->first_name.' '.$user->last_name);

        return $name !== '' ? $name : 'there';
    }

    public static function companyOrName(?User $user): string
    {
        if ($user === null) {
            return config('app.name');
        }

        $user->loadMissing('company');
        $company = $user->company?->company_name;

        if (is_string($company) && trim($company) !== '') {
            return trim($company);
        }

        $name = trim($user->first_name.' '.$user->last_name);

        return $name !== '' ? $name : ($user->email ?? config('app.name'));
    }

    /**
     * @return Collection<int, User>
     */
    public static function adminRecipients(): Collection
    {
        return User::query()->isAdmin()->get();
    }

    public static function sendIfEmail(?User $user, callable $callback, ?string $notificationType = null): void
    {
        if ($user === null || $user->email === null || $user->email === '') {
            return;
        }

        if ($notificationType !== null && ! UserNotificationPreferenceGate::allowsEmail($user, $notificationType)) {
            return;
        }

        $callback($user->email);
    }

    /**
     * @return array<string, mixed>
     */
    public static function passwordResetOtpMailPayload(User $user, string $otp): array
    {
        $ttlMinutes = (int) config('account.password_reset_otp_ttl_minutes', 15);
        $expiresIn = $ttlMinutes.' '.($ttlMinutes === 1 ? 'minute' : 'minutes');

        return [
            'otp' => $otp,
            'formattedOtp' => preg_replace('/(\d{3})(?=\d)/', '$1 ', $otp),
            'recipientName' => self::displayName($user),
            'ttlMinutes' => $ttlMinutes,
            'expiresIn' => $expiresIn,
            'ctaUrl' => self::frontendUrl('auth/restore-account'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function otpMailPayload(string $otp, string $translationPrefix, ?string $expires = null): array
    {
        return [
            'otp' => $otp,
            'preheader' => __($translationPrefix.'.intro'),
            'intro' => __($translationPrefix.'.intro'),
            'headerEyebrow' => __('mail.layout.otp_eyebrow'),
            'headerTitle' => __($translationPrefix.'.title'),
            'headerSubtitle' => config('app.name'),
            'expires' => $expires,
            'footerNote' => __('mail.layout.footer_default'),
        ];
    }

    public static function initials(?string $name): string
    {
        $parts = preg_split('/\s+/', trim((string) $name)) ?: [];

        if ($parts === []) {
            return 'SN';
        }

        return strtoupper(collect($parts)->take(2)->map(fn (string $part): string => mb_substr($part, 0, 1))->implode(''));
    }
}

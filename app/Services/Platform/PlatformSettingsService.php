<?php

namespace App\Services\Platform;

use App\Models\PlatformSetting;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PlatformSettingsService
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public function defaults(): array
    {
        return [
            'general' => [
                'platform_name' => 'SourceNest',
                'support_email' => 'support@sourcenest.com',
                'contact_phone' => '+1 (800) 555-0123',
            ],
            'security' => [
                'require_email_verification' => true,
                'manual_supplier_approval' => true,
                'rate_limiting' => true,
            ],
            'notifications' => [
                'new_supplier_registrations' => true,
                'reported_content' => true,
                'daily_summary' => false,
            ],
            'email' => [
                'from_name' => 'SourceNest Team',
                'from_email' => 'noreply@sourcenest.com',
            ],
            'localization' => [
                'default_language' => 'en',
                'default_currency' => 'USD',
                'default_timezone' => 'UTC',
            ],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function all(): array
    {
        $defaults = $this->defaults();
        $stored = PlatformSetting::query()->get()->keyBy('group');

        $settings = [];

        foreach ($defaults as $group => $defaultPayload) {
            $payload = $stored->get($group)?->payload ?? [];
            $settings[$group] = array_merge($defaultPayload, is_array($payload) ? $payload : []);
        }

        return $settings;
    }

    /**
     * @param  array<string, array<string, mixed>>  $payload
     * @return array<string, array<string, mixed>>
     */
    public function update(User $admin, array $payload): array
    {
        return DB::transaction(function () use ($admin, $payload): array {
            foreach ($payload as $group => $groupPayload) {
                if (! is_array($groupPayload) || ! array_key_exists($group, $this->defaults())) {
                    continue;
                }

                $merged = array_merge($this->defaults()[$group], $groupPayload);

                PlatformSetting::query()->updateOrCreate(
                    ['group' => $group],
                    [
                        'payload' => $merged,
                        'updated_by' => $admin->id,
                    ],
                );
            }

            return $this->all();
        });
    }
}

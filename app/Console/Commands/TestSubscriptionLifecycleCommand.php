<?php

namespace App\Console\Commands;

use App\Enums\Api\V1\BillingInterval;
use App\Enums\Api\V1\Payment\RegisterPaymentManager;
use App\Enums\Api\V1\SubscriptionStatus;
use App\Enums\UserManuFactureStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Services\Payment\PaymentFailedNotificationService;
use App\Services\Subscription\SubscriptionLifecycleService;
use App\Services\Subscription\SubscriptionNotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TestSubscriptionLifecycleCommand extends Command
{
    protected $signature = 'subscriptions:test-lifecycle
                            {scenario : reminder|payment-failed|expired|renewed|activated|list}
                            {email=meheduvau@gmail.com : Destination inbox (Gmail +alias is applied per scenario)}
                            {--fire : After making manufacturer eligible, send/queue that scenario mail now}
                            {--via-scheduler : Prefer real artisan commands (reminder/expired) instead of direct service calls}
                            {--nullify : After expired mail, delete subscription so manufacturer.subscription is null}';

    protected $description = 'Prepare one manufacturer for a single subscription mail scenario (test one-after-one).';

    /** @var list<string> */
    private const SCENARIOS = [
        'reminder',
        'payment-failed',
        'expired',
        'renewed',
        'activated',
    ];

    public function handle(
        SubscriptionLifecycleService $lifecycleService,
        SubscriptionNotificationService $notificationService,
        PaymentFailedNotificationService $paymentFailedNotificationService,
    ): int {
        $scenario = strtolower(trim((string) $this->argument('scenario')));

        if ($scenario === 'list') {
            $this->printGuide();

            return self::SUCCESS;
        }

        if (! in_array($scenario, self::SCENARIOS, true)) {
            $this->error("Unknown scenario [{$scenario}]. Use: ".implode(', ', self::SCENARIOS).', list');

            return self::FAILURE;
        }

        $plan = Plan::query()->orderBy('id')->first();

        if ($plan === null) {
            $this->error('No plans found. Run PlanSeeder first.');

            return self::FAILURE;
        }

        $definition = $this->definitionFor($scenario, (string) $this->argument('email'), $plan);
        $manufacturer = $this->upsertManufacturer($definition);
        $subscription = $this->upsertSubscription($manufacturer, $definition);

        $this->info("Manufacturer ready for [{$scenario}]");
        $this->table(
            ['Field', 'Value'],
            [
                ['email', $manufacturer->email],
                ['user_id', (string) $manufacturer->id],
                ['subscription_id', (string) $subscription->id],
                ['status', $subscription->status instanceof SubscriptionStatus
                    ? $subscription->status->value
                    : (string) $subscription->status],
                ['ends_at', optional($subscription->ends_at)?->toDateTimeString() ?? 'null'],
                ['auto_renew', $subscription->auto_renew ? 'yes' : 'no'],
                ['expiry_reminder_sent_at', optional($subscription->expiry_reminder_sent_at)?->toDateTimeString() ?? 'null'],
                ['template', $definition['template']],
            ],
        );

        if (! $this->option('fire')) {
            $this->newLine();
            $this->warn('Eligible only — no mail sent yet.');
            $this->line($definition['manual_hint']);
            $this->line("Or re-run with --fire:");
            $this->line("  php artisan subscriptions:test-lifecycle {$scenario} {$manufacturer->email} --fire".($scenario === 'expired' ? ' --nullify' : ''));

            return self::SUCCESS;
        }

        $ok = $this->fire(
            $scenario,
            $manufacturer->fresh(),
            $subscription->fresh(['plan', 'manufacturer']),
            $plan,
            $lifecycleService,
            $notificationService,
            $paymentFailedNotificationService,
        );

        if (! $ok) {
            return self::FAILURE;
        }

        $this->newLine();
        $this->info('Done. Keep `php artisan queue:work` running and check the inbox.');
        $this->printNext($scenario);

        return self::SUCCESS;
    }

    private function fire(
        string $scenario,
        User $manufacturer,
        Subscription $subscription,
        Plan $plan,
        SubscriptionLifecycleService $lifecycleService,
        SubscriptionNotificationService $notificationService,
        PaymentFailedNotificationService $paymentFailedNotificationService,
    ): bool {
        $viaScheduler = (bool) $this->option('via-scheduler');

        return match ($scenario) {
            'reminder' => $this->fireReminder($subscription, $lifecycleService, $viaScheduler),
            'payment-failed' => $this->firePaymentFailed($manufacturer, $subscription, $plan, $paymentFailedNotificationService),
            'expired' => $this->fireExpired($subscription, $lifecycleService, $viaScheduler),
            'renewed' => $this->fireRenewed($subscription, $notificationService, $plan),
            'activated' => $this->fireActivated($subscription, $notificationService, $plan),
            default => false,
        };
    }

    private function fireReminder(
        Subscription $subscription,
        SubscriptionLifecycleService $lifecycleService,
        bool $viaScheduler,
    ): bool {
        if ($viaScheduler) {
            Artisan::call('subscriptions:send-expiry-reminders');
            $this->line(trim(Artisan::output()));
            $this->info('✓ Queued via subscriptions:send-expiry-reminders');

            return true;
        }

        if (! $lifecycleService->sendExpiryReminder($subscription)) {
            $this->error('✗ Not eligible for expiry reminder (check ends_at date / already sent).');

            return false;
        }

        $this->info('✓ subscription-expiry-reminder sent/queued');

        return true;
    }

    private function firePaymentFailed(
        User $manufacturer,
        Subscription $subscription,
        Plan $plan,
        PaymentFailedNotificationService $paymentFailedNotificationService,
    ): bool {
        $paymentFailedNotificationService->notify(
            $manufacturer,
            $subscription->plan?->name ?? $plan->name,
        );

        $subscription->update([
            'renew_attempts' => max(1, (int) $subscription->renew_attempts + 1),
            'last_renew_attempt_at' => now(),
            'status' => SubscriptionStatus::PAST_DUE->value,
            'auto_renew' => false,
        ]);

        $this->info('✓ payment-failed sent/queued (auto-pay failure state applied)');

        return true;
    }

    private function fireExpired(
        Subscription $subscription,
        SubscriptionLifecycleService $lifecycleService,
        bool $viaScheduler,
    ): bool {
        if ($viaScheduler) {
            Artisan::call('subscriptions:process-expired');
            $this->line(trim(Artisan::output()));
            $this->info('✓ Queued via subscriptions:process-expired');

            if ($this->option('nullify')) {
                $this->warn('Note: --nullify with --via-scheduler deletes after the job may still be queued; prefer --fire without --via-scheduler for nullify.');
            }

            return true;
        }

        if (! $lifecycleService->processExpiredSubscription($subscription)) {
            $this->error('✗ Not eligible for expiry processing.');

            return false;
        }

        $this->info('✓ subscription-expired sent/queued');

        if ($this->option('nullify')) {
            $manufacturerId = $subscription->manufacturer_id;
            Subscription::query()->whereKey($subscription->id)->delete();
            $this->info("✓ subscription deleted — manufacturer #{$manufacturerId} subscription is now null");
        }

        return true;
    }

    private function fireRenewed(
        Subscription $subscription,
        SubscriptionNotificationService $notificationService,
        Plan $plan,
    ): bool {
        $notificationService->sendSubscriptionRenewed(
            $subscription->fresh(['manufacturer', 'plan']),
            (float) ($plan->monthly_price ?? 0),
        );
        $this->info('✓ subscription-renewed sent/queued');

        return true;
    }

    private function fireActivated(
        Subscription $subscription,
        SubscriptionNotificationService $notificationService,
        Plan $plan,
    ): bool {
        $notificationService->sendSubscriptionCreated(
            $subscription->fresh(['manufacturer', 'plan']),
            (float) ($plan->monthly_price ?? 0),
        );
        $this->info('✓ subscription-activated sent/queued');

        return true;
    }

    /**
     * @return array<string, mixed>
     */
    private function definitionFor(string $scenario, string $baseEmail, Plan $plan): array
    {
        [$local, $domain] = $this->splitEmail($baseEmail);
        $reminderDays = (int) config('subscription.expiry_reminder_days', 7);

        $common = [
            'plan' => $plan,
            'payment_method' => RegisterPaymentManager::PAYPAL->value,
            'renew_attempts' => 0,
            'last_name' => 'Tester',
        ];

        return match ($scenario) {
            'reminder' => [
                ...$common,
                'email' => "{$local}+sub-reminder@{$domain}",
                'first_name' => 'Reminder',
                'company_name' => 'Lifecycle Reminder Co',
                'template' => 'subscription-expiry-reminder',
                'status' => SubscriptionStatus::ACTIVE,
                'auto_renew' => true,
                'ends_at' => now()->addDays($reminderDays)->endOfDay(),
                'starts_at' => now()->subMonth(),
                'expiry_reminder_sent_at' => null,
                'paypal_vault_id' => null,
                'manual_hint' => 'Real scheduler path: php artisan subscriptions:send-expiry-reminders',
            ],
            'payment-failed' => [
                ...$common,
                'email' => "{$local}+sub-payfail@{$domain}",
                'first_name' => 'PayFail',
                'company_name' => 'Lifecycle AutoPay Fail Co',
                'template' => 'payment-failed',
                'status' => SubscriptionStatus::ACTIVE,
                'auto_renew' => true,
                'ends_at' => now()->subHour(),
                'starts_at' => now()->subMonth(),
                'expiry_reminder_sent_at' => now()->subDays(7),
                'paypal_vault_id' => 'TEST-INVALID-VAULT-'.Str::upper(Str::random(8)),
                'manual_hint' => 'Real auto-renew path: php artisan subscriptions:auto-renew (needs valid queue + PayPal fail). --fire uses PaymentFailedNotificationService directly.',
            ],
            'expired' => [
                ...$common,
                'email' => "{$local}+sub-expired@{$domain}",
                'first_name' => 'Expired',
                'company_name' => 'Lifecycle Expired Co',
                'template' => 'subscription-expired',
                'status' => SubscriptionStatus::ACTIVE,
                'auto_renew' => false,
                'ends_at' => now()->subDay(),
                'starts_at' => now()->subMonth()->subDay(),
                'expiry_reminder_sent_at' => now()->subDays(8),
                'paypal_vault_id' => null,
                'manual_hint' => 'Real scheduler path: php artisan subscriptions:process-expired',
            ],
            'renewed' => [
                ...$common,
                'email' => "{$local}+sub-renewed@{$domain}",
                'first_name' => 'Renewed',
                'company_name' => 'Lifecycle Renewed Co',
                'template' => 'subscription-renewed',
                'status' => SubscriptionStatus::ACTIVE,
                'auto_renew' => true,
                'ends_at' => now()->addMonth(),
                'starts_at' => now()->subDay(),
                'expiry_reminder_sent_at' => null,
                'paypal_vault_id' => null,
                'manual_hint' => 'Real success needs PayPal vault charge. --fire sends renewed mail via SubscriptionNotificationService.',
            ],
            'activated' => [
                ...$common,
                'email' => "{$local}+sub-activated@{$domain}",
                'first_name' => 'Activated',
                'company_name' => 'Lifecycle Activated Co',
                'template' => 'subscription-activated',
                'status' => SubscriptionStatus::ACTIVE,
                'auto_renew' => true,
                'ends_at' => now()->addMonth(),
                'starts_at' => now(),
                'expiry_reminder_sent_at' => null,
                'paypal_vault_id' => null,
                'manual_hint' => 'Real path is subscribe API success. --fire sends activated mail via SubscriptionNotificationService.',
            ],
            default => throw new \InvalidArgumentException("Unknown scenario [{$scenario}]"),
        };
    }

    /**
     * @param  array<string, mixed>  $scenario
     */
    private function upsertManufacturer(array $scenario): User
    {
        $manufacturer = User::query()->updateOrCreate(
            ['email' => $scenario['email']],
            [
                'first_name' => $scenario['first_name'],
                'last_name' => $scenario['last_name'],
                'password' => Hash::make($scenario['email']),
                'role' => UserRole::MANUFACTURER->value,
                'status' => UserStatus::ACTIVE->value,
                'agreed_to_terms' => true,
                'manufacture_status' => UserManuFactureStatus::APPROVED->value,
                'manufacture_status_at' => now(),
            ],
        );

        if ($manufacturer->company === null) {
            $manufacturer->company()->create([
                'company_name' => $scenario['company_name'],
                'short_description' => 'Lifecycle mail test manufacturer.',
                'company_type' => 'manufacturer',
                'country' => 'United States',
                'city' => 'Chicago',
            ]);
        }

        return $manufacturer->fresh();
    }

    /**
     * @param  array<string, mixed>  $scenario
     */
    private function upsertSubscription(User $manufacturer, array $scenario): Subscription
    {
        /** @var Plan $plan */
        $plan = $scenario['plan'];

        $attributes = [
            'plan_id' => $plan->id,
            'billing_interval' => BillingInterval::MONTH->value,
            'status' => $scenario['status']->value,
            'starts_at' => $scenario['starts_at'],
            'ends_at' => $scenario['ends_at'],
            'trial_ends_at' => null,
            'auto_renew' => $scenario['auto_renew'],
            'payment_method' => $scenario['payment_method'],
            'paypal_vault_id' => $scenario['paypal_vault_id'],
            'renew_attempts' => $scenario['renew_attempts'],
            'last_renew_attempt_at' => null,
            'expiry_reminder_sent_at' => $scenario['expiry_reminder_sent_at'],
        ];

        $existing = $manufacturer->subscription;

        if ($existing !== null) {
            $existing->update($attributes);

            return $existing->fresh(['plan', 'manufacturer']);
        }

        return Subscription::query()->create([
            'manufacturer_id' => $manufacturer->id,
            ...$attributes,
        ])->load(['plan', 'manufacturer']);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function splitEmail(string $email): array
    {
        $parts = explode('@', strtolower(trim($email)), 2);

        if (count($parts) !== 2 || $parts[0] === '' || $parts[1] === '') {
            return ['lifecycle', 'example.com'];
        }

        $local = explode('+', $parts[0], 2)[0];

        return [$local, $parts[1]];
    }

    private function printGuide(): void
    {
        $this->info('Test subscription mails one after one:');
        $this->newLine();
        $this->line('1) reminder');
        $this->line('   php artisan subscriptions:test-lifecycle reminder --fire');
        $this->line('2) payment-failed');
        $this->line('   php artisan subscriptions:test-lifecycle payment-failed --fire');
        $this->line('3) expired');
        $this->line('   php artisan subscriptions:test-lifecycle expired --fire --nullify');
        $this->line('4) renewed');
        $this->line('   php artisan subscriptions:test-lifecycle renewed --fire');
        $this->line('5) activated');
        $this->line('   php artisan subscriptions:test-lifecycle activated --fire');
        $this->newLine();
        $this->line('Prepare only (no send): omit --fire, then run the real scheduler command shown.');
        $this->line('Use --via-scheduler with reminder/expired to go through Artisan schedule commands.');
    }

    private function printNext(string $scenario): void
    {
        $order = self::SCENARIOS;
        $index = array_search($scenario, $order, true);

        if ($index === false || $index >= count($order) - 1) {
            $this->line('All scenarios covered. `php artisan subscriptions:test-lifecycle list` to restart.');

            return;
        }

        $next = $order[$index + 1];
        $extra = $next === 'expired' ? ' --nullify' : '';
        $this->line("Next: php artisan subscriptions:test-lifecycle {$next} --fire{$extra}");
    }
}

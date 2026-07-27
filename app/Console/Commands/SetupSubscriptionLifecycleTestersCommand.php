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
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SetupSubscriptionLifecycleTestersCommand extends Command
{
    protected $signature = 'subscriptions:setup-lifecycle-testers
                            {email=meheduvau@gmail.com : Inbox that receives the live mails (Gmail +aliases used)}
                            {--fire : Queue/send the live lifecycle emails after seeding}
                            {--nullify-expired : After expired mail, delete that subscription so manufacturer.subscription is null}';

    protected $description = 'Create 4 manufacturers that match reminder / payment-failed / expired / renewed conditions, optionally fire live mails.';

    public function handle(
        SubscriptionLifecycleService $lifecycleService,
        SubscriptionNotificationService $notificationService,
        PaymentFailedNotificationService $paymentFailedNotificationService,
    ): int {
        $plan = Plan::query()->orderBy('id')->first();

        if ($plan === null) {
            $this->error('No plans found. Run PlanSeeder first.');

            return self::FAILURE;
        }

        $baseEmail = strtolower(trim((string) $this->argument('email')));
        $scenarios = $this->scenarios($baseEmail, $plan);

        $created = [];

        foreach ($scenarios as $key => $scenario) {
            $manufacturer = $this->upsertManufacturer($scenario);
            $subscription = $this->upsertSubscription($manufacturer, $scenario);

            $created[$key] = [
                'user' => $manufacturer,
                'subscription' => $subscription,
                'email' => $scenario['email'],
                'template' => $scenario['template'],
                'notes' => $scenario['notes'],
            ];

            $this->info(sprintf(
                '[%s] %s → %s (sub #%d, ends_at=%s, auto_renew=%s)',
                $key,
                $scenario['email'],
                $scenario['template'],
                $subscription->id,
                optional($subscription->ends_at)?->toDateTimeString() ?? 'null',
                $subscription->auto_renew ? 'yes' : 'no',
            ));
        }

        if (! $this->option('fire')) {
            $this->newLine();
            $this->warn('Seeded only. Re-run with --fire to send live emails (queue:work must be running).');
            $this->line('Example: php artisan subscriptions:setup-lifecycle-testers meheduvau@gmail.com --fire --nullify-expired');

            return self::SUCCESS;
        }

        $this->newLine();
        $this->info('Firing live lifecycle emails…');

        // 1) Expiry reminder (real eligibility path)
        $reminder = $created['reminder'];
        if ($lifecycleService->sendExpiryReminder($reminder['subscription']->fresh())) {
            $this->info("✓ expiry-reminder → {$reminder['email']}");
        } else {
            $this->warn("✗ expiry-reminder skipped (not eligible) → {$reminder['email']}");
        }

        // 2) Payment failed (auto-pay failure path notification)
        $failed = $created['payment_failed'];
        $paymentFailedNotificationService->notify(
            $failed['user']->fresh(),
            $failed['subscription']->plan?->name ?? $plan->name,
        );
        $failed['subscription']->update([
            'renew_attempts' => max(1, (int) $failed['subscription']->renew_attempts),
            'last_renew_attempt_at' => now(),
            'status' => SubscriptionStatus::PAST_DUE->value,
            'auto_renew' => false,
        ]);
        $this->info("✓ payment-failed → {$failed['email']}");

        // 3) Expired notice (real eligibility path), then optional nullify
        $expired = $created['expired'];
        $expiredSub = $expired['subscription']->fresh();
        if ($lifecycleService->processExpiredSubscription($expiredSub)) {
            $this->info("✓ subscription-expired → {$expired['email']}");

            if ($this->option('nullify-expired')) {
                $manufacturerId = $expiredSub->manufacturer_id;
                Subscription::query()->whereKey($expiredSub->id)->delete();
                $this->info("✓ subscription deleted for manufacturer #{$manufacturerId} (subscription is now null)");
            } else {
                $this->line('  (kept as past_due; pass --nullify-expired to delete the row)');
            }
        } else {
            $this->warn("✗ subscription-expired skipped (not eligible) → {$expired['email']}");
        }

        // 4) Renewed (successful auto-pay outcome mail)
        $renewed = $created['renewed'];
        $notificationService->sendSubscriptionRenewed(
            $renewed['subscription']->fresh(['manufacturer', 'plan']),
            (float) ($plan->monthly_price ?? 0),
        );
        $this->info("✓ subscription-renewed → {$renewed['email']}");

        // Bonus: activated mail on the renewed manufacturer so you can also check template #5
        $notificationService->sendSubscriptionCreated(
            $renewed['subscription']->fresh(['manufacturer', 'plan']),
            (float) ($plan->monthly_price ?? 0),
        );
        $this->info("✓ subscription-activated (bonus) → {$renewed['email']}");

        $this->newLine();
        $this->info('Queued/sent. Keep `php artisan queue:work` running and check Mailgun → inbox.');
        $this->table(
            ['Scenario', 'Email', 'Template'],
            collect($created)->map(fn (array $row, string $key): array => [
                $key,
                $row['email'],
                $row['template'],
            ])->values()->all(),
        );

        return self::SUCCESS;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function scenarios(string $baseEmail, Plan $plan): array
    {
        [$local, $domain] = $this->splitEmail($baseEmail);
        $reminderDays = (int) config('subscription.expiry_reminder_days', 7);

        return [
            'reminder' => [
                'email' => "{$local}+sub-reminder@{$domain}",
                'first_name' => 'Reminder',
                'last_name' => 'Tester',
                'company_name' => 'Lifecycle Reminder Co',
                'template' => 'subscription-expiry-reminder',
                'notes' => "ends_at = today + {$reminderDays} days",
                'plan' => $plan,
                'status' => SubscriptionStatus::ACTIVE,
                'auto_renew' => true,
                'ends_at' => now()->addDays($reminderDays)->endOfDay(),
                'starts_at' => now()->subMonth(),
                'expiry_reminder_sent_at' => null,
                'payment_method' => RegisterPaymentManager::PAYPAL->value,
                'paypal_vault_id' => null,
                'renew_attempts' => 0,
            ],
            'payment_failed' => [
                'email' => "{$local}+sub-payfail@{$domain}",
                'first_name' => 'PayFail',
                'last_name' => 'Tester',
                'company_name' => 'Lifecycle AutoPay Fail Co',
                'template' => 'payment-failed',
                'notes' => 'due auto-renew with invalid vault → payment failed mail',
                'plan' => $plan,
                'status' => SubscriptionStatus::ACTIVE,
                'auto_renew' => true,
                'ends_at' => now()->subHour(),
                'starts_at' => now()->subMonth(),
                'expiry_reminder_sent_at' => now()->subDays(7),
                'payment_method' => RegisterPaymentManager::PAYPAL->value,
                'paypal_vault_id' => 'TEST-INVALID-VAULT-'.Str::upper(Str::random(8)),
                'renew_attempts' => 0,
            ],
            'expired' => [
                'email' => "{$local}+sub-expired@{$domain}",
                'first_name' => 'Expired',
                'last_name' => 'Tester',
                'company_name' => 'Lifecycle Expired Co',
                'template' => 'subscription-expired',
                'notes' => 'past ends_at, auto_renew off → expire + optional nullify',
                'plan' => $plan,
                'status' => SubscriptionStatus::ACTIVE,
                'auto_renew' => false,
                'ends_at' => now()->subDay(),
                'starts_at' => now()->subMonth()->subDay(),
                'expiry_reminder_sent_at' => now()->subDays(8),
                'payment_method' => RegisterPaymentManager::PAYPAL->value,
                'paypal_vault_id' => null,
                'renew_attempts' => 0,
            ],
            'renewed' => [
                'email' => "{$local}+sub-renewed@{$domain}",
                'first_name' => 'Renewed',
                'last_name' => 'Tester',
                'company_name' => 'Lifecycle Renewed Co',
                'template' => 'subscription-renewed (+ activated bonus)',
                'notes' => 'active subscription used to send renewed + activated mails',
                'plan' => $plan,
                'status' => SubscriptionStatus::ACTIVE,
                'auto_renew' => true,
                'ends_at' => now()->addMonth(),
                'starts_at' => now()->subDay(),
                'expiry_reminder_sent_at' => null,
                'payment_method' => RegisterPaymentManager::PAYPAL->value,
                'paypal_vault_id' => null,
                'renew_attempts' => 0,
            ],
        ];
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
        $parts = explode('@', $email, 2);

        if (count($parts) !== 2 || $parts[0] === '' || $parts[1] === '') {
            return ['lifecycle', 'example.com'];
        }

        // Strip existing +tag so we control aliases.
        $local = explode('+', $parts[0], 2)[0];

        return [$local, $parts[1]];
    }
}

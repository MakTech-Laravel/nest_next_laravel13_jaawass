<?php

namespace Database\Seeders;

use App\Enums\Api\V1\BillingInterval;
use App\Enums\Api\V1\SubscriptionEventType;
use App\Enums\Api\V1\SubscriptionStatus;
use App\Enums\UserManuFactureStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionLog;
use App\Models\User;
use App\Services\Company\CompanySlugService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ManufacturerSubscriptionSeeder extends Seeder
{
    /**
     * Seed demo manufacturers with subscriptions, payments, and logs.
     *
     * Run manually:
     *   php artisan db:seed --class=ManufacturerSubscriptionSeeder
     */
    public function run(): void
    {
        $plans = Plan::query()->orderBy('id')->get()->keyBy('name');

        if ($plans->isEmpty()) {
            $this->command->error('No plans found. Run PlanSeeder first.');

            return;
        }

        $professional = $plans->get('Professional') ?? $plans->values()->get(1);
        $enterprise = $plans->get('Enterprise') ?? $plans->values()->last();

        if ($professional === null || $enterprise === null) {
            $this->command->error('Expected at least Professional and Enterprise plans.');

            return;
        }

        $slugService = app(CompanySlugService::class);

        $scenarios = [
            [
                'email' => 'sub-manufacturer-1@dev.com',
                'first_name' => 'Alex',
                'last_name' => 'Chen',
                'company_name' => 'Pacific Components Ltd.',
                'plan' => $professional,
                'billing_interval' => BillingInterval::MONTH,
                'status' => SubscriptionStatus::ACTIVE,
                'auto_renew' => true,
                'started_days_ago' => 10,
                'payments' => [
                    ['suffix' => 'initial', 'amount' => $professional->monthly_price, 'days_ago' => 10],
                ],
                'logs' => [
                    ['event' => SubscriptionEventType::SUBSCRIPTION_CREATED, 'from' => null, 'to' => $professional, 'days_ago' => 10],
                ],
            ],
            [
                'email' => 'sub-manufacturer-2@dev.com',
                'first_name' => 'Sara',
                'last_name' => 'Khan',
                'company_name' => 'Nordic Apparel Manufacturing',
                'plan' => $enterprise,
                'billing_interval' => BillingInterval::YEAR,
                'status' => SubscriptionStatus::ACTIVE,
                'auto_renew' => true,
                'started_days_ago' => 45,
                'payments' => [
                    ['suffix' => 'initial', 'amount' => $professional->monthly_price, 'days_ago' => 60],
                    ['suffix' => 'upgrade', 'amount' => $enterprise->yearly_price, 'days_ago' => 45],
                ],
                'logs' => [
                    ['event' => SubscriptionEventType::SUBSCRIPTION_CREATED, 'from' => null, 'to' => $professional, 'days_ago' => 60],
                    ['event' => SubscriptionEventType::SUBSCRIPTION_UPGRADED, 'from' => $professional, 'to' => $enterprise, 'days_ago' => 45],
                ],
            ],
            [
                'email' => 'sub-manufacturer-3@dev.com',
                'first_name' => 'Marco',
                'last_name' => 'Rossi',
                'company_name' => 'EuroSteel Fabrication SRL',
                'plan' => $professional,
                'billing_interval' => BillingInterval::MONTH,
                'status' => SubscriptionStatus::ACTIVE,
                'auto_renew' => false,
                'started_days_ago' => 20,
                'payments' => [
                    ['suffix' => 'initial', 'amount' => $professional->monthly_price, 'days_ago' => 20],
                ],
                'logs' => [
                    ['event' => SubscriptionEventType::SUBSCRIPTION_CREATED, 'from' => null, 'to' => $professional, 'days_ago' => 20],
                    ['event' => SubscriptionEventType::SUBSCRIPTION_CANCELLED, 'from' => $professional, 'to' => $professional, 'days_ago' => 5, 'paid_amount' => null],
                ],
            ],
            [
                'email' => 'sub-manufacturer-4@dev.com',
                'first_name' => 'Yuki',
                'last_name' => 'Tanaka',
                'company_name' => 'Tokyo Precision Tools Inc.',
                'plan' => $enterprise,
                'billing_interval' => BillingInterval::MONTH,
                'status' => SubscriptionStatus::TRIALING,
                'auto_renew' => true,
                'started_days_ago' => 3,
                'trial_days' => 14,
                'payments' => [],
                'logs' => [
                    ['event' => SubscriptionEventType::SUBSCRIPTION_CREATED, 'from' => null, 'to' => $enterprise, 'days_ago' => 3, 'paid_amount' => null],
                ],
            ],
            [
                'email' => 'sub-manufacturer-5@dev.com',
                'first_name' => 'Emily',
                'last_name' => 'Johnson',
                'company_name' => 'Summit Home Goods LLC',
                'plan' => $professional,
                'billing_interval' => BillingInterval::MONTH,
                'status' => SubscriptionStatus::CANCELED,
                'auto_renew' => false,
                'started_days_ago' => 90,
                'ended_days_ago' => 5,
                'payments' => [
                    ['suffix' => 'initial', 'amount' => $professional->monthly_price, 'days_ago' => 90],
                    ['suffix' => 'renewal', 'amount' => $professional->monthly_price, 'days_ago' => 60],
                ],
                'logs' => [
                    ['event' => SubscriptionEventType::SUBSCRIPTION_CREATED, 'from' => null, 'to' => $professional, 'days_ago' => 90],
                    ['event' => SubscriptionEventType::SUBSCRIPTION_RENEWED, 'from' => $professional, 'to' => $professional, 'days_ago' => 60],
                    ['event' => SubscriptionEventType::SUBSCRIPTION_CANCELLED, 'from' => $professional, 'to' => $professional, 'days_ago' => 30, 'paid_amount' => null],
                    ['event' => SubscriptionEventType::SUBSCRIPTION_EXPIRED, 'from' => $professional, 'to' => $professional, 'days_ago' => 5, 'paid_amount' => null],
                ],
            ],
        ];

        foreach ($scenarios as $scenario) {
            $manufacturer = $this->ensureManufacturer($scenario, $slugService);
            $this->seedSubscriptionData($manufacturer, $scenario);
        }

        $this->seedExistingManufacturers($professional, $enterprise);

        $this->command->info('Manufacturer subscription demo data seeded.');
        $this->command->line('Login passwords match email (e.g. sub-manufacturer-1@dev.com).');
    }

    /**
     * @param  array<string, mixed>  $scenario
     */
    private function ensureManufacturer(array $scenario, CompanySlugService $slugService): User
    {
        $manufacturer = User::query()->firstOrCreate(
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
            $company = $manufacturer->company()->create([
                'company_name' => $scenario['company_name'],
                'short_description' => 'Demo manufacturer for subscription testing.',
                'long_description' => 'Seeded company profile for admin subscription and payment screens.',
                'company_type' => 'manufacturer',
                'company_established' => '2016',
                'company_size' => '50-200',
                'revenue' => '$1M - $5M',
                'country' => 'United States',
                'city' => 'Chicago',
                'street_address' => '500 Industrial Blvd',
                'phone' => '+1 312 555 0100',
                'zip_code' => '60601',
                'minimum_order_value' => 2500,
                'company_website' => 'https://example.com',
                'capabilities' => json_encode(['OEM', 'Private label']),
                'certifications' => json_encode(['ISO9001']),
                'export_markets' => json_encode(['North America', 'Europe']),
                'language_spoken' => json_encode(['English']),
                'payments_term' => json_encode(['T/T', 'PayPal']),
            ]);

            $slugService->syncSlug($company, $scenario['company_name']);
        }

        return $manufacturer;
    }

    /**
     * @param  array<string, mixed>  $scenario
     */
    private function seedSubscriptionData(User $manufacturer, array $scenario): void
    {
        if ($manufacturer->subscription !== null) {
            $this->command->warn("Skipping {$manufacturer->email} — subscription already exists.");

            return;
        }

        /** @var Plan $plan */
        $plan = $scenario['plan'];
        $billingInterval = $scenario['billing_interval'];
        $startsAt = now()->subDays($scenario['started_days_ago']);

        $endsAt = match ($billingInterval) {
            BillingInterval::YEAR => $startsAt->copy()->addYear(),
            default => $startsAt->copy()->addMonth(),
        };

        if (isset($scenario['ended_days_ago'])) {
            $endsAt = now()->subDays($scenario['ended_days_ago']);
        }

        $trialEndsAt = isset($scenario['trial_days'])
            ? $startsAt->copy()->addDays($scenario['trial_days'])
            : null;

        $subscription = Subscription::query()->create([
            'manufacturer_id' => $manufacturer->id,
            'plan_id' => $plan->id,
            'billing_interval' => $billingInterval->value,
            'status' => $scenario['status']->value,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'trial_ends_at' => $trialEndsAt,
            'auto_renew' => $scenario['auto_renew'],
            'created_at' => $startsAt,
            'updated_at' => now(),
        ]);

        foreach ($scenario['payments'] as $payment) {
            $paidAt = now()->subDays($payment['days_ago']);

            Payment::query()->create([
                'payment_id' => $this->paymentId($manufacturer->email, $payment['suffix']),
                'payment_method' => 'paypal',
                'amount' => $payment['amount'],
                'status' => 'paid',
                'user_id' => $manufacturer->id,
                'subscription_id' => $subscription->id,
                'source_id' => $plan->id,
                'source_type' => Plan::class,
                'created_at' => $paidAt,
                'updated_at' => $paidAt,
            ]);
        }

        foreach ($scenario['logs'] as $log) {
            $loggedAt = now()->subDays($log['days_ago']);

            SubscriptionLog::query()->create([
                'manufacturer_id' => $manufacturer->id,
                'from_plan_id' => $log['from']?->id,
                'to_plan_id' => $log['to']->id,
                'event_type' => $log['event']->value,
                'paid_amount' => array_key_exists('paid_amount', $log)
                    ? $log['paid_amount']
                    : ($log['event'] === SubscriptionEventType::SUBSCRIPTION_CANCELLED
                        ? null
                        : ($billingInterval === BillingInterval::YEAR ? $log['to']->yearly_price : $log['to']->monthly_price)),
                'created_at' => $loggedAt,
                'updated_at' => $loggedAt,
            ]);
        }

        $this->command->info("Seeded subscription for {$manufacturer->email} ({$plan->name}, {$scenario['status']->value}).");
    }

    private function seedExistingManufacturers(Plan $professional, Plan $enterprise): void
    {
        $existing = [
            'manufacturer@dev.com' => [
                'plan' => $professional,
                'billing_interval' => BillingInterval::MONTH,
                'status' => SubscriptionStatus::ACTIVE,
                'auto_renew' => true,
                'started_days_ago' => 7,
                'amount' => $professional->monthly_price,
            ],
            'meheduvau@gmail.com' => [
                'plan' => $enterprise,
                'billing_interval' => BillingInterval::YEAR,
                'status' => SubscriptionStatus::ACTIVE,
                'auto_renew' => true,
                'started_days_ago' => 15,
                'amount' => $enterprise->yearly_price,
            ],
        ];

        foreach ($existing as $email => $config) {
            $manufacturer = User::query()->where('email', $email)->first();

            if ($manufacturer === null) {
                continue;
            }

            if ($manufacturer->subscription !== null) {
                continue;
            }

            $startsAt = now()->subDays($config['started_days_ago']);
            $endsAt = $config['billing_interval'] === BillingInterval::YEAR
                ? $startsAt->copy()->addYear()
                : $startsAt->copy()->addMonth();

            $subscription = Subscription::query()->create([
                'manufacturer_id' => $manufacturer->id,
                'plan_id' => $config['plan']->id,
                'billing_interval' => $config['billing_interval']->value,
                'status' => $config['status']->value,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'trial_ends_at' => null,
                'auto_renew' => $config['auto_renew'],
                'created_at' => $startsAt,
                'updated_at' => now(),
            ]);

            $paymentId = $this->paymentId($email, 'existing');

            Payment::query()->create([
                'payment_id' => $paymentId,
                'payment_method' => 'paypal',
                'amount' => $config['amount'],
                'status' => 'paid',
                'user_id' => $manufacturer->id,
                'subscription_id' => $subscription->id,
                'source_id' => $config['plan']->id,
                'source_type' => Plan::class,
                'created_at' => $startsAt,
                'updated_at' => $startsAt,
            ]);

            SubscriptionLog::query()->create([
                'manufacturer_id' => $manufacturer->id,
                'from_plan_id' => null,
                'to_plan_id' => $config['plan']->id,
                'event_type' => SubscriptionEventType::SUBSCRIPTION_CREATED->value,
                'paid_amount' => $config['amount'],
                'created_at' => $startsAt,
                'updated_at' => $startsAt,
            ]);

            $this->command->info("Seeded subscription for existing manufacturer {$email}.");
        }
    }

    private function paymentId(string $email, string $suffix): string
    {
        $hash = substr(md5($email), 0, 8);

        return "SEED-PAYPAL-{$hash}-{$suffix}";
    }
}

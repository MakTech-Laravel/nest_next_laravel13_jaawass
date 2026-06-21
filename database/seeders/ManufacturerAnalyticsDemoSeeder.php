<?php

namespace Database\Seeders;

use App\Enums\DashboardEventType;
use App\Enums\RfqSubmissionStatus;
use App\Enums\UserManuFactureStatus;
use App\Enums\UserRole;
use App\Models\Conversation;
use App\Models\DashboardEvent;
use App\Models\Product;
use App\Models\RfqSubmission;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class ManufacturerAnalyticsDemoSeeder extends Seeder
{
    private const SEED_MARKER = 'manufacturer_analytics_demo';

    /**
     * Seed product views and RFQ inquiries so manufacturer analytics APIs return data.
     *
     * Run manually:
     *   php artisan db:seed --class=ManufacturerAnalyticsDemoSeeder
     */
    public function run(): void
    {
        $buyer = User::query()
            ->where('role', UserRole::BUYER->value)
            ->orderBy('id')
            ->first();

        if ($buyer === null) {
            $this->command->error('No buyer user found. Run UserSeeder first.');

            return;
        }

        $manufacturers = User::query()
            ->where('role', UserRole::MANUFACTURER->value)
            ->where('manufacture_status', UserManuFactureStatus::APPROVED->value)
            ->whereHas('products')
            ->orderBy('id')
            ->get();

        if ($manufacturers->isEmpty()) {
            $this->command->warn('No approved manufacturers with products found. Run ProductSeeder first.');

            return;
        }

        $this->purgePreviousDemoData();

        $totalViews = 0;
        $totalInquiries = 0;
        $totalManufacturers = 0;

        foreach ($manufacturers as $manufacturer) {
            $products = Product::query()
                ->where('user_id', $manufacturer->id)
                ->orderBy('id')
                ->get();

            if ($products->isEmpty()) {
                continue;
            }

            $conversation = $this->conversationFor($buyer, $manufacturer);

            foreach ($products as $index => $product) {
                $viewCount = random_int(8, 45) + ($index * 3);
                $inquiryCount = random_int(1, 8) + (int) floor($index / 2);

                $totalViews += $this->seedProductViews($buyer, $manufacturer, $product, $viewCount);
                $totalInquiries += $this->seedInquiries(
                    $buyer,
                    $manufacturer,
                    $product,
                    $conversation,
                    $inquiryCount,
                );
            }

            $this->seedSupplierProfileViews($buyer, $manufacturer, random_int(5, 20));

            $totalManufacturers++;
            $this->command->info(sprintf(
                'Seeded analytics demo for %s (%s) — %d product(s).',
                $manufacturer->email,
                $manufacturer->id,
                $products->count(),
            ));
        }

        $this->command->info(sprintf(
            'Manufacturer analytics demo complete: %d manufacturer(s), %d product views, %d RFQ inquiries.',
            $totalManufacturers,
            $totalViews,
            $totalInquiries,
        ));
        $this->command->info('Try: GET /api/v1/manufacturer/analytics/products?period=last_30_days (login as manufacturer@dev.com).');
    }

    private function purgePreviousDemoData(): void
    {
        DashboardEvent::query()
            ->where('metadata->seed', self::SEED_MARKER)
            ->delete();

        RfqSubmission::query()
            ->where('additional_requirements', self::SEED_MARKER)
            ->delete();
    }

    private function conversationFor(User $buyer, User $manufacturer): Conversation
    {
        $conversation = Conversation::query()->create([
            'name' => 'Analytics demo — '.$manufacturer->email,
            'created_by' => $buyer->id,
        ]);

        $conversation->participants()->sync([$buyer->id, $manufacturer->id]);

        return $conversation;
    }

    private function seedProductViews(
        User $buyer,
        User $manufacturer,
        Product $product,
        int $count,
    ): int {
        $rows = [];

        for ($i = 0; $i < $count; $i++) {
            $occurredAt = $this->randomDateWithinLastDays(29);

            $rows[] = [
                'actor_user_id' => $buyer->id,
                'counterparty_user_id' => $manufacturer->id,
                'role_context' => 'buyer',
                'event_type' => DashboardEventType::ProductViewed->value,
                'entity_type' => 'product',
                'entity_id' => $product->id,
                'metadata' => json_encode([
                    'seed' => self::SEED_MARKER,
                    'product_name' => $product->name,
                ]),
                'occurred_at' => $occurredAt,
                'created_at' => $occurredAt,
                'updated_at' => $occurredAt,
            ];
        }

        DashboardEvent::query()->insert($rows);

        return $count;
    }

    private function seedSupplierProfileViews(User $buyer, User $manufacturer, int $count): void
    {
        $rows = [];

        for ($i = 0; $i < $count; $i++) {
            $occurredAt = $this->randomDateWithinLastDays(29);

            $rows[] = [
                'actor_user_id' => $buyer->id,
                'counterparty_user_id' => $manufacturer->id,
                'role_context' => 'buyer',
                'event_type' => DashboardEventType::SupplierViewed->value,
                'entity_type' => 'supplier',
                'entity_id' => $manufacturer->id,
                'metadata' => json_encode(['seed' => self::SEED_MARKER]),
                'occurred_at' => $occurredAt,
                'created_at' => $occurredAt,
                'updated_at' => $occurredAt,
            ];
        }

        DashboardEvent::query()->insert($rows);
    }

    private function seedInquiries(
        User $buyer,
        User $manufacturer,
        Product $product,
        Conversation $conversation,
        int $count,
    ): int {
        for ($i = 0; $i < $count; $i++) {
            $createdAt = $this->randomDateWithinLastDays(29);

            RfqSubmission::query()->create([
                'rfq_number' => 'RFQ-DEMO-'.$product->id.'-'.uniqid(),
                'buyer_id' => $buyer->id,
                'manufacturer_id' => $manufacturer->id,
                'product_id' => $product->id,
                'conversation_id' => $conversation->id,
                'quantity' => random_int(50, 500),
                'quantity_unit' => 'pcs',
                'status' => RfqSubmissionStatus::Pending->value,
                'additional_requirements' => self::SEED_MARKER,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
        }

        return $count;
    }

    private function randomDateWithinLastDays(int $daysBack): Carbon
    {
        return now()
            ->subDays(random_int(0, $daysBack))
            ->subHours(random_int(0, 23))
            ->subMinutes(random_int(0, 59));
    }
}

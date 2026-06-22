<?php

namespace Database\Seeders;

use App\Enums\OrderStatus;
use App\Enums\ReviewStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Industry;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use App\Services\Company\CompanySlugService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class ReviewSeeder extends Seeder
{
    /**
     * Seed demo product reviews for the admin moderation UI.
     *
     * Run manually:
     *   php artisan db:seed --class=ReviewSeeder
     */
    public function run(): void
    {
        $techVision = User::query()->where('email', 'manufacturer@dev.com')->first();
        $textileSupplier = User::query()->where('email', 'meheduvau@gmail.com')->first();

        if ($techVision === null) {
            $this->command?->warn('ReviewSeeder skipped: manufacturer@dev.com not found. Run UserSeeder first.');

            return;
        }

        $suppliers = collect([$techVision, $textileSupplier])->filter()->keyBy('email');

        foreach ($suppliers as $supplier) {
            $this->ensureSupplierProducts($supplier);
        }

        $productsByManufacturer = Product::query()
            ->whereIn('user_id', $suppliers->pluck('id'))
            ->orderBy('id')
            ->get()
            ->groupBy('user_id');

        if ($productsByManufacturer->isEmpty()) {
            $this->command?->warn('ReviewSeeder skipped: no products found. Run ProductSeeder first.');

            return;
        }

        $slugService = app(CompanySlugService::class);

        $entries = [
            [
                'buyer' => [
                    'first_name' => 'Lisa',
                    'last_name' => 'Anderson',
                    'email' => 'lisa.anderson@buyer.dev',
                    'company_name' => 'Green Fashion UK',
                    'country' => 'United Kingdom',
                ],
                'supplier_email' => 'meheduvau@gmail.com',
                'rating' => 5,
                'title' => 'Perfect for sustainable brands',
                'comment' => 'EcoThread has been our go-to supplier for organic cotton products. Their GOTS certification is legitimate and they provide full traceability. The quality is exceptional and aligns perfectly with our brand values.',
                'status' => ReviewStatus::PUBLISHED,
                'total_amount' => 37500.00,
                'submitted_at' => '2026-02-20 10:15:00',
                'order_created_at' => '2026-01-05 09:00:00',
            ],
            [
                'buyer' => [
                    'first_name' => 'Carlos',
                    'last_name' => 'Silva',
                    'email' => 'carlos.silva@buyer.dev',
                    'company_name' => 'Auto Parts Brasil',
                    'country' => 'Brazil',
                ],
                'supplier_email' => 'manufacturer@dev.com',
                'rating' => 4,
                'title' => 'Reliable auto parts supplier',
                'comment' => 'Reliable auto parts supplier with consistent quality. Communication was clear throughout production and shipment milestones were met on schedule.',
                'status' => ReviewStatus::PUBLISHED,
                'total_amount' => 18250.00,
                'submitted_at' => '2026-02-18 14:30:00',
                'order_created_at' => '2026-01-12 11:20:00',
            ],
            [
                'buyer' => [
                    'first_name' => 'Michael',
                    'last_name' => 'Johnson',
                    'email' => 'michael.johnson@buyer.dev',
                    'company_name' => 'TechMart USA',
                    'country' => 'United States',
                ],
                'supplier_email' => 'manufacturer@dev.com',
                'rating' => 5,
                'title' => 'Exceptional quality and communication',
                'comment' => 'Exceptional quality and communication from start to finish. Samples matched bulk production and the team was responsive to engineering change requests.',
                'status' => ReviewStatus::PUBLISHED,
                'total_amount' => 42000.00,
                'submitted_at' => '2026-02-15 16:45:00',
                'order_created_at' => '2026-01-08 08:40:00',
            ],
            [
                'buyer' => [
                    'first_name' => 'Emma',
                    'last_name' => 'Wilson',
                    'email' => 'emma.wilson@buyer.dev',
                    'company_name' => 'Nordic Home GmbH',
                    'country' => 'Germany',
                ],
                'supplier_email' => 'manufacturer@dev.com',
                'rating' => 5,
                'title' => 'Strong OEM partner',
                'comment' => 'Strong OEM partner for consumer electronics. Packaging and labeling met EU requirements without rework.',
                'status' => ReviewStatus::PUBLISHED,
                'total_amount' => 28900.00,
                'submitted_at' => '2026-02-12 09:10:00',
                'order_created_at' => '2026-01-03 13:00:00',
            ],
            [
                'buyer' => [
                    'first_name' => 'Raj',
                    'last_name' => 'Patel',
                    'email' => 'raj.patel@buyer.dev',
                    'company_name' => 'Urban Retail India',
                    'country' => 'India',
                ],
                'supplier_email' => 'meheduvau@gmail.com',
                'rating' => 4,
                'title' => 'Good value for bulk apparel',
                'comment' => 'Good value for bulk apparel orders. Minor delay on the first shipment, but quality control improved on the repeat order.',
                'status' => ReviewStatus::PUBLISHED,
                'total_amount' => 15600.00,
                'submitted_at' => '2026-02-10 11:55:00',
                'order_created_at' => '2025-12-28 10:30:00',
            ],
            [
                'buyer' => [
                    'first_name' => 'Sophie',
                    'last_name' => 'Martin',
                    'email' => 'sophie.martin@buyer.dev',
                    'company_name' => 'Maison Paris',
                    'country' => 'France',
                ],
                'supplier_email' => 'meheduvau@gmail.com',
                'rating' => 5,
                'title' => 'Premium fabric quality',
                'comment' => 'Premium fabric quality and excellent dye consistency across the entire production run.',
                'status' => ReviewStatus::PUBLISHED,
                'total_amount' => 33200.00,
                'submitted_at' => '2026-02-08 15:20:00',
                'order_created_at' => '2025-12-20 09:15:00',
            ],
            [
                'buyer' => [
                    'first_name' => 'James',
                    'last_name' => 'OConnor',
                    'email' => 'james.oconnor@buyer.dev',
                    'company_name' => 'Celtic Imports',
                    'country' => 'Ireland',
                ],
                'supplier_email' => 'manufacturer@dev.com',
                'rating' => 3,
                'title' => 'Solid but documentation was slow',
                'comment' => 'Product quality was solid, but compliance documentation arrived later than expected. Would order again with tighter timelines.',
                'status' => ReviewStatus::PENDING,
                'total_amount' => 9800.00,
                'submitted_at' => '2026-02-22 08:05:00',
                'order_created_at' => '2026-01-18 12:00:00',
            ],
            [
                'buyer' => [
                    'first_name' => 'Aisha',
                    'last_name' => 'Khan',
                    'email' => 'aisha.khan@buyer.dev',
                    'company_name' => 'Gulf Trading LLC',
                    'country' => 'United Arab Emirates',
                ],
                'supplier_email' => 'manufacturer@dev.com',
                'rating' => 5,
                'title' => 'Fast turnaround on custom SKU',
                'comment' => 'Fast turnaround on a custom SKU with updated firmware. Engineering support was proactive.',
                'status' => ReviewStatus::PENDING,
                'total_amount' => 21450.00,
                'submitted_at' => '2026-02-21 17:40:00',
                'order_created_at' => '2026-01-20 07:50:00',
            ],
            [
                'buyer' => [
                    'first_name' => 'Daniel',
                    'last_name' => 'Nguyen',
                    'email' => 'daniel.nguyen@buyer.dev',
                    'company_name' => 'Pacific Sourcing Co.',
                    'country' => 'Australia',
                ],
                'supplier_email' => 'meheduvau@gmail.com',
                'rating' => 2,
                'title' => 'Sizing inconsistencies in first batch',
                'comment' => 'Sizing inconsistencies in the first batch required additional QC. Supplier corrected issues on replacement units.',
                'status' => ReviewStatus::FLAGGED,
                'total_amount' => 12400.00,
                'submitted_at' => '2026-02-19 13:25:00',
                'order_created_at' => '2026-01-10 15:45:00',
            ],
            [
                'buyer' => [
                    'first_name' => 'Hannah',
                    'last_name' => 'Becker',
                    'email' => 'hannah.becker@buyer.dev',
                    'company_name' => 'Berlin Outfitters',
                    'country' => 'Germany',
                ],
                'supplier_email' => 'meheduvau@gmail.com',
                'rating' => 4,
                'title' => 'Reliable repeat supplier',
                'comment' => 'Reliable repeat supplier for seasonal collections. MOQ flexibility helped our launch timeline.',
                'status' => ReviewStatus::PUBLISHED,
                'total_amount' => 26800.00,
                'submitted_at' => '2026-02-07 10:00:00',
                'order_created_at' => '2025-12-15 14:10:00',
            ],
            [
                'buyer' => [
                    'first_name' => 'Noah',
                    'last_name' => 'Kim',
                    'email' => 'noah.kim@buyer.dev',
                    'company_name' => 'Seoul Devices',
                    'country' => 'South Korea',
                ],
                'supplier_email' => 'manufacturer@dev.com',
                'rating' => 5,
                'title' => 'Excellent prototype to mass production flow',
                'comment' => 'Excellent prototype to mass production flow. Defect rate was below our threshold on incoming inspection.',
                'status' => ReviewStatus::PUBLISHED,
                'total_amount' => 51200.00,
                'submitted_at' => '2026-02-05 18:15:00',
                'order_created_at' => '2025-12-10 16:30:00',
            ],
            [
                'buyer' => [
                    'first_name' => 'Olivia',
                    'last_name' => 'Brown',
                    'email' => 'olivia.brown@buyer.dev',
                    'company_name' => 'Maple Wholesale',
                    'country' => 'Canada',
                ],
                'supplier_email' => 'manufacturer@dev.com',
                'rating' => 4,
                'title' => 'Good communication during peak season',
                'comment' => 'Good communication during peak season. Freight coordination could be smoother, but product quality remained high.',
                'status' => ReviewStatus::HIDDEN,
                'total_amount' => 19300.00,
                'submitted_at' => '2026-02-04 12:40:00',
                'order_created_at' => '2025-12-08 11:05:00',
            ],
            [
                'buyer' => [
                    'first_name' => 'Marco',
                    'last_name' => 'Rossi',
                    'email' => 'marco.rossi@buyer.dev',
                    'company_name' => 'Milano Retail Group',
                    'country' => 'Italy',
                ],
                'supplier_email' => 'meheduvau@gmail.com',
                'rating' => 5,
                'title' => 'Beautiful finishing on garments',
                'comment' => 'Beautiful finishing on garments and accurate color matching against our reference swatches.',
                'status' => ReviewStatus::PUBLISHED,
                'total_amount' => 30100.00,
                'submitted_at' => '2026-02-02 09:30:00',
                'order_created_at' => '2025-12-05 08:20:00',
            ],
            [
                'buyer' => [
                    'first_name' => 'Priya',
                    'last_name' => 'Sharma',
                    'email' => 'priya.sharma@buyer.dev',
                    'company_name' => 'Delhi Fashion House',
                    'country' => 'India',
                ],
                'supplier_email' => 'meheduvau@gmail.com',
                'rating' => 4,
                'title' => 'Competitive pricing on basics',
                'comment' => 'Competitive pricing on basics with acceptable lead times for our private label program.',
                'status' => ReviewStatus::PENDING,
                'total_amount' => 14750.00,
                'submitted_at' => '2026-02-22 07:50:00',
                'order_created_at' => '2026-01-22 10:10:00',
            ],
            [
                'buyer' => [
                    'first_name' => 'Ethan',
                    'last_name' => 'Clark',
                    'email' => 'ethan.clark@buyer.dev',
                    'company_name' => 'West Coast Components',
                    'country' => 'United States',
                ],
                'supplier_email' => 'manufacturer@dev.com',
                'rating' => 5,
                'title' => 'Top-tier assembly quality',
                'comment' => 'Top-tier assembly quality and helpful post-delivery support when we updated packaging artwork.',
                'status' => ReviewStatus::PUBLISHED,
                'total_amount' => 38900.00,
                'submitted_at' => '2026-01-30 14:05:00',
                'order_created_at' => '2025-11-28 13:40:00',
            ],
            [
                'buyer' => [
                    'first_name' => 'Yuki',
                    'last_name' => 'Tanaka',
                    'email' => 'yuki.tanaka@buyer.dev',
                    'company_name' => 'Tokyo Gadgets',
                    'country' => 'Japan',
                ],
                'supplier_email' => 'manufacturer@dev.com',
                'rating' => 4,
                'title' => 'Consistent component tolerances',
                'comment' => 'Consistent component tolerances across three production lots. Sample approval process was efficient.',
                'status' => ReviewStatus::PUBLISHED,
                'total_amount' => 27600.00,
                'submitted_at' => '2026-01-28 11:18:00',
                'order_created_at' => '2025-11-22 09:55:00',
            ],
            [
                'buyer' => [
                    'first_name' => 'Fatima',
                    'last_name' => 'Al-Sayed',
                    'email' => 'fatima.alsayed@buyer.dev',
                    'company_name' => 'Gulf Apparel Trading',
                    'country' => 'United Arab Emirates',
                ],
                'supplier_email' => 'meheduvau@gmail.com',
                'rating' => 1,
                'title' => 'Unverified claims in listing',
                'comment' => 'Review mentions certification claims that do not match submitted documents. Flagged for moderation.',
                'status' => ReviewStatus::FLAGGED,
                'total_amount' => 8900.00,
                'submitted_at' => '2026-02-20 19:10:00',
                'order_created_at' => '2026-01-14 17:25:00',
            ],
            [
                'buyer' => [
                    'first_name' => 'Lucas',
                    'last_name' => 'Dubois',
                    'email' => 'lucas.dubois@buyer.dev',
                    'company_name' => 'Lyon Distribution',
                    'country' => 'France',
                ],
                'supplier_email' => 'manufacturer@dev.com',
                'rating' => 5,
                'title' => 'Smooth onboarding for first order',
                'comment' => 'Smooth onboarding for our first order. Account manager helped align incoterms and inspection checkpoints.',
                'status' => ReviewStatus::PUBLISHED,
                'total_amount' => 16750.00,
                'submitted_at' => '2026-01-25 10:22:00',
                'order_created_at' => '2025-11-18 12:35:00',
            ],
        ];

        $created = 0;
        $skipped = 0;

        foreach ($entries as $index => $entry) {
            if (Review::query()->where('title', $entry['title'])->exists()) {
                $skipped++;

                continue;
            }

            $supplier = $suppliers->get($entry['supplier_email']);

            if ($supplier === null) {
                $skipped++;

                continue;
            }

            $product = $productsByManufacturer->get($supplier->id)?->values()->get($index % max($productsByManufacturer->get($supplier->id)?->count() ?? 1, 1));

            if ($product === null) {
                $skipped++;

                continue;
            }

            $buyer = $this->resolveBuyer($entry['buyer'], $slugService);
            $orderCreatedAt = Carbon::parse($entry['order_created_at']);
            $submittedAt = Carbon::parse($entry['submitted_at']);

            $order = Order::query()->create([
                'user_id' => $supplier->id,
                'buyer_id' => $buyer->id,
                'manufacturer_id' => $supplier->id,
                'product_id' => $product->id,
                'title' => "Completed order for {$product->name}",
                'quantity' => 500 + ($index * 25),
                'quantity_unit' => 'pieces',
                'total_amount' => $entry['total_amount'],
                'currency_code' => 'USD',
                'estimated_delivery_at' => $orderCreatedAt->copy()->addDays(30)->toDateString(),
                'delivered_at' => $orderCreatedAt->copy()->addDays(28),
                'status' => OrderStatus::Completed,
                'created_at' => $orderCreatedAt,
                'updated_at' => $orderCreatedAt->copy()->addDays(28),
            ]);

            Review::query()->create([
                'user_id' => $supplier->id,
                'product_id' => $product->id,
                'order_id' => $order->id,
                'reviewer_id' => $buyer->id,
                'rating' => $entry['rating'],
                'title' => $entry['title'],
                'comment' => $entry['comment'],
                'status' => $entry['status']->value,
                'created_at' => $submittedAt,
                'updated_at' => $submittedAt,
            ]);

            $created++;
        }

        $this->command?->info("ReviewSeeder finished: {$created} created, {$skipped} skipped.");
    }

    /**
     * @param  array{first_name: string, last_name: string, email: string, company_name: string, country: string}  $profile
     */
    private function resolveBuyer(array $profile, CompanySlugService $slugService): User
    {
        $buyer = User::query()->firstOrCreate(
            ['email' => $profile['email']],
            [
                'first_name' => $profile['first_name'],
                'last_name' => $profile['last_name'],
                'password' => Hash::make($profile['email']),
                'role' => UserRole::BUYER->value,
                'status' => UserStatus::ACTIVE->value,
                'agreed_to_terms' => true,
            ],
        );

        if ($buyer->company === null) {
            $company = $buyer->company()->create([
                'company_name' => $profile['company_name'],
                'country' => $profile['country'],
                'city' => 'Demo City',
                'street_address' => '100 Buyer Street',
                'phone' => '+1 555 0100',
                'zip_code' => '00000',
            ]);

            $slugService->syncSlug($company, $profile['company_name']);
        }

        return $buyer->fresh('company');
    }

    private function ensureSupplierProducts(User $supplier): void
    {
        if (Product::query()->where('user_id', $supplier->id)->exists()) {
            return;
        }

        $industry = Industry::query()->orderBy('id')->first();
        $subCategoryId = $industry?->subCategories()->orderBy('id')->value('id');

        if ($industry === null || $subCategoryId === null) {
            return;
        }

        $catalog = [
            ['name' => 'Organic Cotton T-Shirts', 'slug' => 'organic-cotton-t-shirts'],
            ['name' => 'Recycled Polyester Hoodies', 'slug' => 'recycled-polyester-hoodies'],
            ['name' => 'Private Label Denim', 'slug' => 'private-label-denim'],
        ];

        foreach ($catalog as $item) {
            Product::query()->firstOrCreate(
                [
                    'user_id' => $supplier->id,
                    'slug' => $item['slug'],
                ],
                [
                    'industry_id' => $industry->id,
                    'sub_category_id' => $subCategoryId,
                    'name' => $item['name'],
                    'description' => "Demo catalog product for {$supplier->email}.",
                    'status' => 'active',
                    'is_approved' => true,
                    'currency_id' => 1,
                ],
            );
        }
    }
}

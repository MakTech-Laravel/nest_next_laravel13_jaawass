<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            // ── Foundation (locales & currencies) ───────────────────────────
            LanguageSeeder::class,
            CurrencySeeder::class,
            CurrencyRateSeeder::class, // requires currencies

            // ── Catalog structure ───────────────────────────────────────────
            IndustrySeeder::class,
            SubCategorySeeder::class, // requires industries
            ShippingMethodSeeder::class,

            // ── FAQ ─────────────────────────────────────────────────────────
            FaqCategorySeeder::class,
            FaqSeeder::class, // requires faq categories

            // ── Subscription plans ──────────────────────────────────────────
            FeatureSeeder::class,
            PlanSeeder::class, // requires currencies
            PlanFeatureSeeder::class, // requires features & plans
            PromotionSeeder::class, // requires plans

            // ── Users & manufacturer profiles ───────────────────────────────
            UserSeeder::class,
            UserNotificationSeeder::class, // requires users
            ManufacturerCompanySeeder::class, // requires users & industries
            ManufacturerSubscriptionSeeder::class, // requires plans (demo manufacturers + subscriptions)

            // ── Products ──────────────────────────────────────────────────
            ProductSeeder::class, // requires users, industries & sub-categories

            // ── Help center ─────────────────────────────────────────────────
            HelpCenterCategorySeeder::class,
            HelpCenterArticleSeeder::class, // requires help center categories

            // ── Derived / backfill data ─────────────────────────────────────
            DashboardEventBackfillSeeder::class, // backfills events from existing RFQs, orders, etc.
            ManufacturerAnalyticsDemoSeeder::class, // product views + RFQs for analytics/products API
        ]);
    }
}

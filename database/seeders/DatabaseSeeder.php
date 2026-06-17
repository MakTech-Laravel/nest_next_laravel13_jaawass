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
        // User::factory(10)->create();

        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        $this->call([
            LanguageSeeder::class,
            CurrencySeeder::class,
            CurrencyRateSeeder::class,
            UserSeeder::class,
            ManufacturerCompanySeeder::class,
            UserNotificationSeeder::class,
            IndustrySeeder::class,
            SubCategorySeeder::class,
            ShippingMethodSeeder::class,
            ProductSeeder::class,
            FaqCategorySeeder::class,
            FaqSeeder::class,
            FeatureSeeder::class,
            PlanSeeder::class,
            PromotionSeeder::class,
            HelpCenterCategorySeeder::class,
            HelpCenterArticleSeeder::class,
        ]);
    }
}

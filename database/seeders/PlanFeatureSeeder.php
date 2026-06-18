<?php

namespace Database\Seeders;

use App\Models\Feature;
use App\Models\Plan;
use App\Models\PlanFeature;
use Illuminate\Database\Seeder;

class PlanFeatureSeeder extends Seeder
{
    /**
     * Assign default feature packages to Starter, Growth, and Enterprise plans.
     *
     * Run manually:
     *   php artisan db:seed --class=PlanFeatureSeeder
     */
    public function run(): void
    {
        $features = Feature::query()->pluck('id', 'key');

        if ($features->isEmpty()) {
            $this->command->error('No features found. Run FeatureSeeder first.');

            return;
        }

        $packages = [
            'Starter' => [
                ['key' => 'product_limit', 'input_type' => 'text', 'value' => '100'],
                ['key' => 'company_profile', 'input_type' => 'boolean', 'value' => '1'],
                ['key' => 'internal_messaging', 'input_type' => 'boolean', 'value' => '1'],
                ['key' => 'inquiry_rfq_inbox', 'input_type' => 'boolean', 'value' => '1'],
                ['key' => 'catalog_upload', 'input_type' => 'boolean', 'value' => '1'],
                ['key' => 'basic_analytics', 'input_type' => 'boolean', 'value' => '1'],
                ['key' => 'certifications_section', 'input_type' => 'boolean', 'value' => '1'],
                ['key' => 'export_markets_section', 'input_type' => 'boolean', 'value' => '1'],
                ['key' => 'limited_buyer_visibility', 'input_type' => 'boolean', 'value' => '1'],
            ],
            'Growth' => [
                ['key' => 'product_limit', 'input_type' => 'text', 'value' => '500'],
                ['key' => 'company_profile', 'input_type' => 'boolean', 'value' => '1'],
                ['key' => 'internal_messaging', 'input_type' => 'boolean', 'value' => '1'],
                ['key' => 'inquiry_rfq_inbox', 'input_type' => 'boolean', 'value' => '1'],
                ['key' => 'catalog_upload', 'input_type' => 'boolean', 'value' => '1'],
                ['key' => 'advanced_analytics', 'input_type' => 'boolean', 'value' => '1'],
                ['key' => 'certifications_section', 'input_type' => 'boolean', 'value' => '1'],
                ['key' => 'export_markets_section', 'input_type' => 'boolean', 'value' => '1'],
                ['key' => 'priority_search_visibility', 'input_type' => 'boolean', 'value' => '1'],
                ['key' => 'featured_supplier_badge', 'input_type' => 'boolean', 'value' => '1'],
                ['key' => 'team_users_limit', 'input_type' => 'text', 'value' => '3'],
                ['key' => 'enhanced_buyer_visibility', 'input_type' => 'boolean', 'value' => '1'],
                ['key' => 'higher_chance_receive_rfq', 'input_type' => 'boolean', 'value' => '1'],
            ],
            'Enterprise' => [
                ['key' => 'product_limit', 'input_type' => 'text', 'value' => 'unlimited'],
                ['key' => 'company_profile', 'input_type' => 'boolean', 'value' => '1'],
                ['key' => 'internal_messaging', 'input_type' => 'boolean', 'value' => '1'],
                ['key' => 'inquiry_rfq_inbox', 'input_type' => 'boolean', 'value' => '1'],
                ['key' => 'catalog_upload', 'input_type' => 'boolean', 'value' => '1'],
                ['key' => 'advanced_analytics', 'input_type' => 'boolean', 'value' => '1'],
                ['key' => 'certifications_section', 'input_type' => 'boolean', 'value' => '1'],
                ['key' => 'export_markets_section', 'input_type' => 'boolean', 'value' => '1'],
                ['key' => 'priority_search_visibility', 'input_type' => 'boolean', 'value' => '1'],
                ['key' => 'premium_search_placement', 'input_type' => 'boolean', 'value' => '1'],
                ['key' => 'featured_supplier_badge', 'input_type' => 'boolean', 'value' => '1'],
                ['key' => 'unlimited_team_users', 'input_type' => 'boolean', 'value' => '1'],
                ['key' => 'maximum_buyer_visibility', 'input_type' => 'boolean', 'value' => '1'],
                ['key' => 'higher_chance_receive_rfq', 'input_type' => 'boolean', 'value' => '1'],
                ['key' => 'higher_priority_buyer_inquiries', 'input_type' => 'boolean', 'value' => '1'],
                ['key' => 'priority_support', 'input_type' => 'boolean', 'value' => '1'],
            ],
        ];

        foreach ($packages as $planName => $planFeatures) {
            $plan = Plan::query()->where('name', $planName)->first();

            if ($plan === null) {
                $this->command->warn("Plan \"{$planName}\" not found — skipped.");

                continue;
            }

            PlanFeature::query()->where('plan_id', $plan->id)->delete();

            foreach ($planFeatures as $featureConfig) {
                $featureId = $features->get($featureConfig['key']);

                if ($featureId === null) {
                    $this->command->warn("Feature \"{$featureConfig['key']}\" not found — skipped.");

                    continue;
                }

                PlanFeature::query()->create([
                    'plan_id' => $plan->id,
                    'feature_id' => $featureId,
                    'input_type' => $featureConfig['input_type'],
                    'value' => $featureConfig['value'],
                ]);
            }

            $this->command->info("Assigned ".count($planFeatures)." features to {$planName}.");
        }
    }
}

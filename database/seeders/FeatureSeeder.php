<?php

namespace Database\Seeders;

use App\Models\Feature;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class FeatureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $features = [
            ['name' => 'Product Limit', 'key' => 'product_limit'],
            ['name' => 'Company Profile', 'key' => 'company_profile'],
            ['name' => 'Internal Messaging', 'key' => 'internal_messaging'],
            ['name' => 'Inquiry Inbox & RFQ Reception', 'key' => 'inquiry_rfq_inbox'],
            ['name' => 'Catalog Upload', 'key' => 'catalog_upload'],
        
            ['name' => 'Basic Analytics', 'key' => 'basic_analytics'],
            ['name' => 'Advanced Analytics', 'key' => 'advanced_analytics'],
        
            ['name' => 'Certifications Section', 'key' => 'certifications_section'],
            ['name' => 'Export Markets Section', 'key' => 'export_markets_section'],
        
            ['name' => 'Limited Buyer Visibility', 'key' => 'limited_buyer_visibility'],
            ['name' => 'Enhanced Buyer Visibility', 'key' => 'enhanced_buyer_visibility'],
            ['name' => 'Maximum Buyer Visibility', 'key' => 'maximum_buyer_visibility'],
        
            ['name' => 'Priority Search Visibility', 'key' => 'priority_search_visibility'],
            ['name' => 'Premium Search Placement', 'key' => 'premium_search_placement'],
        
            ['name' => 'Featured Supplier Badge', 'key' => 'featured_supplier_badge'],
        
            ['name' => 'Team Users Limit', 'key' => 'team_users_limit'],
            ['name' => 'Unlimited Team Users', 'key' => 'unlimited_team_users'],
        
            ['name' => 'Higher Chance to Receive RFQs', 'key' => 'higher_chance_receive_rfq'],
            ['name' => 'Higher Priority in Buyer Inquiries', 'key' => 'higher_priority_buyer_inquiries'],
        
            ['name' => 'Priority Support', 'key' => 'priority_support'],
        ];

        Feature::insert($features);
        
        $this->command->info('Features seeded successfully!');
    }
}

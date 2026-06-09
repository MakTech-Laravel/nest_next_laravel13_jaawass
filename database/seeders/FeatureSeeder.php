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
            ['name' => 'Product Limits', 'key' => 'product_limits'],
            ['name' => 'Company Profile', 'key' => 'company_profile'],
            ['name' => 'Intnernal Messaging', 'key' => 'internal_messaging'],
            ['name' => 'Intnernal Inbox & RFQ reception', 'key' => 'inqury_rfq_inbox'],
            ['name' => 'Basic Analytics', 'key' => 'basic_analytics'],
            ['name' => 'Advanced Analytics', 'key' => 'advanced_analytics'],
            ['name' => 'Catalog Upload', 'key'=>'catalog_upload'],
            ['name' => 'Certification section', 'key'=>'certification_section'],
            ['name' => 'Export markets Section', 'key'=>'export_market_section'],
            ['name' => 'Limited Visiblity', 'key'=>'limited_visiblity'],
            ['name' => 'Priority Serach Visiblity', 'key'=>'priority_search_visiblity'],
            ['name' => 'Featured Supplier Badge', 'key'=>'featured_supplier_badge'],
            ['name' => 'Multiple team users', 'key'=>'multiple_team_user'],
            ['name' => 'Enhanced Visiblity Buyers', 'key'=>'enhanced_visiblity_buyers'],
            ['name' => 'Higher Changes to Recievef RFQ', 'key'=>'higher_chances_to_receive_rfq'],
            ['name' => 'Unlimited Products', 'key'=>'unlimted_products'],
            ['name' => 'Everything in Growth +', 'key'=>'growth_plus'],
            ['name' => 'Permium (top) Search Placement', 'key'=>'premium_search_placement'],
            ['name' => 'Maximum Visiblity', 'key'=>'maximum_visiblity'],
            ['name' => 'Higher Priority in Buyer inquiries', 'key'=>'higher_priority_buyer_inquiries'],
            ['name' => 'Unlimted team users', 'key'=>'unlimted_team_users'],
            ['name' => 'Priority Support', 'key'=>'priority_support'],
        ];

        Feature::insert($features);
        
        $this->command->info('Features seeded successfully!');
    }
}

<?php

namespace Database\Seeders;

use App\Models\HelpCenterCategory;
use Illuminate\Database\Seeder;

class HelpCenterCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        HelpCenterCategory::create([
            'name' => 'For Buyers',
            'slug' => 'for-buyers',
            'description' => '
            This category contains help articles for buyers.
            It includes articles on how to use the platform, how to find products, how to contact sellers, and how to place orders.
            It also includes articles on how to use the platform, how to find products, how to contact sellers, and how to place orders.
            It also includes articles on how to use the platform, how to find products, how to contact sellers, and how to place orders.
            It also includes articles on how to use the platform, how to find products, how to contact sellers, and how to place orders.
            It also includes articles on how to use the platform, how to find products, how to contact sellers, and how to place orders.',
            'sort_order' => 1,
        ]);
        HelpCenterCategory::create([
            'name' => 'For Manufacturers    ',
            'slug' => 'for-manufacturers',
            'description' => '
            This category contains help articles for manufacturers.
            It includes articles on how to use the platform, how to find products, how to contact sellers, and how to place orders.
            It also includes articles on how to use the platform, how to find products, how to contact sellers, and how to place orders.
            It also includes articles on how to use the platform, how to find products, how to contact sellers, and how to place orders.',
            'sort_order' => 2,
        ]);
        HelpCenterCategory::create([
            'name' => 'Billing & Payments',
            'slug' => 'billing-and-payments',
            'description' => '
            This category contains help articles for billing and payments.
            It includes articles on how to use the platform, how to find products, how to contact sellers, and how to place orders.
            It also includes articles on how to use the platform, how to find products, how to contact sellers, and how to place orders.
            It also includes articles on how to use the platform, how to find products, how to contact sellers, and how to place orders.',
            'sort_order' => 3,
            ]);
            HelpCenterCategory::create([
                'name' => 'Review & Approval',
                'slug' => 'review-and-approval',
                'description' => '
                This category contains help articles for review and approval.
                It includes articles on how to use the platform, how to find products, how to contact sellers, and how to place orders.
                It also includes articles on how to use the platform, how to find products, how to contact sellers, and how to place orders.
                It also includes articles on how to use the platform, how to find products, how to contact sellers, and how to place orders.',
                'sort_order' => 4,
            ]);

            
            HelpCenterCategory::create([
                'name' => 'Account & Settings',
                'slug' => 'account-and-settings',
                'description' => '
                This category contains help articles for account and settings.
                It includes articles on how to use the platform, how to find products, how to contact sellers, and how to place orders.
                It also includes articles on how to use the platform, how to find products, how to contact sellers, and how to place orders.
                It also includes articles on how to use the platform, how to find products, how to contact sellers, and how to place orders.
                It also includes articles on how to use the platform, how to find products, how to contact sellers, and how to place orders.',
                'sort_order' => 5,
            ]);
            HelpCenterCategory::create([
                'name' => 'Technical Support',
                'slug' => 'technical-support',
                'description' => '
                This category contains help articles for technical support.
                It includes articles on how to use the platform, how to find products, how to contact sellers, and how to place orders.
                It also includes articles on how to use the platform, how to find products, how to contact sellers, and how to place orders.
                It also includes articles on how to use the platform, how to find products, how to contact sellers, and how to place orders.
                It also includes articles on how to use the platform, how to find products, how to contact sellers, and how to place orders.',
                'sort_order' => 6,
            ]);
    }
}

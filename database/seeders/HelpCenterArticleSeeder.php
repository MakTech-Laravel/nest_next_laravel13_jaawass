<?php

namespace Database\Seeders;

use App\Models\HelpCenterArticle;
use App\Models\HelpCenterArticleStep;
use App\Models\HelpCenterCategory;
use Illuminate\Database\Seeder;

class HelpCenterArticleSeeder extends Seeder
{
    public function run(): void
    {
        $articlesByCategory = [
            'for-buyers' => [
                [
                    'title' => 'How to search for products',
                    'description' => 'Find the right suppliers and products using filters and categories.',
                    'steps' => [
                        'Use the search bar with product keywords or industry names',
                        'Apply filters for country, certification, and MOQ',
                        'Save suppliers to your favorites for quick access',
                        'Send RFQs to multiple manufacturers at once',
                    ],
                ],
                [
                    'title' => 'Placing your first order',
                    'description' => 'A guide to requesting quotes and confirming orders with suppliers.',
                    'steps' => [
                        'Review supplier profiles and product details',
                        'Submit an RFQ with quantity and delivery requirements',
                        'Compare quotes and negotiate terms in messages',
                        'Confirm the order and track shipment status',
                    ],
                ],
            ],
            'for-manufacturers' => [
                [
                    'title' => 'Understanding your analytics',
                    'description' => 'Use analytics to optimize your presence and improve conversion rates.',
                    'steps' => [
                        'Check profile views and visitor demographics',
                        'Monitor product inquiry rates and popular items',
                        'Track response time and message statistics',
                        'Analyze conversion rates from inquiry to quote',
                        'Identify trends and adjust your strategy accordingly',
                    ],
                ],
                [
                    'title' => 'Optimizing your manufacturer profile',
                    'description' => 'Make your profile stand out to international buyers.',
                    'steps' => [
                        'Upload high-quality factory and product images',
                        'Complete certifications and export markets',
                        'Keep product listings updated with accurate MOQ and lead times',
                        'Respond to inquiries within 24 hours',
                    ],
                ],
            ],
            'billing-and-payments' => [
                [
                    'title' => 'Subscription plans explained',
                    'description' => 'Compare plans and understand what is included in each tier.',
                    'steps' => [
                        'Review plan features on the pricing page',
                        'Choose monthly or yearly billing',
                        'Upgrade or downgrade from account settings',
                        'Download invoices from billing history',
                    ],
                ],
            ],
            'account-and-settings' => [
                [
                    'title' => 'Updating account preferences',
                    'description' => 'Manage language, currency, and notification settings.',
                    'steps' => [
                        'Open Account Settings from your profile menu',
                        'Set your preferred language and currency',
                        'Choose which email notifications to receive',
                        'Save changes and verify your email if prompted',
                    ],
                ],
            ],
        ];

        foreach ($articlesByCategory as $categorySlug => $articles) {
            $category = HelpCenterCategory::query()->where('slug', $categorySlug)->first();

            if (! $category) {
                continue;
            }

            foreach ($articles as $index => $articleData) {
                $article = HelpCenterArticle::query()->create([
                    'help_center_category_id' => $category->id,
                    'title' => $articleData['title'],
                    'description' => $articleData['description'],
                    'help_full' => 0,
                    'not_help_full' => 0,
                    'sort_order' => $index + 1,
                    'status' => true,
                ]);

                foreach ($articleData['steps'] as $stepIndex => $stepContent) {
                    HelpCenterArticleStep::query()->create([
                        'help_center_article_id' => $article->id,
                        'content' => $stepContent,
                        'sort_order' => $stepIndex + 1,
                    ]);
                }
            }
        }
    }
}

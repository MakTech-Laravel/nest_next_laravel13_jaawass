<?php

namespace Database\Seeders;

use App\Models\AboutPage;
use Illuminate\Database\Seeder;

class AboutPageSeeder extends Seeder
{
    public function run(): void
    {
        $content = [
            'hero' => [
                'title' => 'Making Global Sourcing Work Better',
                'subtitle' => 'SourceNest is on a mission to transform how businesses find and connect with manufacturing partners worldwide.',
            ],
            'story' => [
                'title' => 'Our Story',
                'paragraphs' => [
                    'Global sourcing has always been challenging. Buyers struggle to find reliable suppliers, review their legitimacy, and communicate effectively across borders. Manufacturers, especially quality-focused ones, have difficulty standing out among countless options and reaching serious buyers.',
                    'SourceNest was born from a simple idea: what if there was a platform that only featured reviewed, approved manufacturers? A place where buyers could know that every supplier had been screened based on submitted information before being listed?',
                    'We built SourceNest to be that platform. By requiring admin approval for every manufacturer and keeping the platform free for buyers, we\'ve created an environment where quality prevails and trust is the foundation of every connection.',
                    'Today, SourceNest connects thousands of buyers with reviewed manufacturers across 50+ countries, covering 45+ industries. We\'re proud to be making global trade more accessible, transparent, and efficient.',
                ],
            ],
            'mission' => [
                'title' => 'Our Mission',
                'description' => 'To make global sourcing more transparent, efficient, and trustworthy by connecting quality-focused buyers with reviewed manufacturers through a premium digital platform.',
            ],
            'vision' => [
                'title' => 'Our Vision',
                'description' => 'A world where finding the right manufacturing partner is simple, safe, and successful — regardless of geography, company size, or industry.',
            ],
            'values' => [
                'title' => 'Our Values',
                'subtitle' => 'The principles that guide everything we do',
                'items' => [
                    [
                        'id' => 'trust',
                        'icon' => 'Shield',
                        'title' => 'Trust & Transparency',
                        'description' => 'We believe sourcing should be built on trust. Every supplier is reviewed based on submitted information, and we strive to maintain platform quality.',
                        'enabled' => true,
                    ],
                    [
                        'id' => 'global',
                        'icon' => 'Globe',
                        'title' => 'Global Accessibility',
                        'description' => 'We\'re breaking down barriers in international trade, making it easier for businesses of all sizes to connect across borders.',
                        'enabled' => true,
                    ],
                    [
                        'id' => 'community',
                        'icon' => 'Users',
                        'title' => 'Community First',
                        'description' => 'We\'re building more than a platform — we\'re creating a community of quality-focused buyers and manufacturers.',
                        'enabled' => true,
                    ],
                    [
                        'id' => 'innovation',
                        'icon' => 'Lightbulb',
                        'title' => 'Innovation',
                        'description' => 'We continuously improve our platform with smart features that make sourcing more efficient and effective.',
                        'enabled' => true,
                    ],
                ],
            ],
            'why_different' => [
                'title' => 'Why SourceNest is Different',
                'points' => [
                    [
                        'id' => 'reviewed',
                        'title' => 'Reviewed-Only Marketplace:',
                        'description' => 'Unlike open platforms where anyone can list, every manufacturer on SourceNest goes through our review and approval process based on submitted information. This means buyers know that suppliers have been screened, and manufacturers know they\'re in good company.',
                        'enabled' => true,
                    ],
                    [
                        'id' => 'free',
                        'title' => 'Free for Buyers:',
                        'description' => 'We believe buyers should have access to quality sourcing tools without barriers. By making the platform free for buyers, we ensure maximum reach for manufacturers and maximum access for sourcing professionals.',
                        'enabled' => true,
                    ],
                    [
                        'id' => 'nocommission',
                        'title' => 'No Commission Model:',
                        'description' => 'We don\'t take a cut of your deals. Manufacturers pay only their subscription fee, and all communication and negotiation happens directly between parties.',
                        'enabled' => true,
                    ],
                    [
                        'id' => 'premium',
                        'title' => 'Premium Focus:',
                        'description' => 'We\'re not trying to be the biggest platform — we\'re trying to be the most trusted. Quality over quantity is our guiding principle.',
                        'enabled' => true,
                    ],
                ],
            ],
            'cta' => [
                'title' => 'Join the SourceNest Community',
                'subtitle' => 'Whether you\'re sourcing products or manufacturing them, we\'d love to have you.',
                'buyer_button_text' => 'Join as Buyer',
                'manufacturer_button_text' => 'Join as Manufacturer',
            ],
        ];

        $page = AboutPage::query()->first();

        if ($page === null) {
            $page = AboutPage::query()->create([
                'enabled' => true,
                'content' => $content,
            ]);
        } else {
            $page->update([
                'enabled' => true,
                'content' => $content,
            ]);
        }

        $sourceLocale = (string) config('translation.source_locale', 'en');

        $page->translations()->updateOrCreate(
            ['locale' => $sourceLocale],
            ['content' => $content]
        );
    }
}

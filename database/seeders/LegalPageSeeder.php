<?php

namespace Database\Seeders;

use App\Models\LegalPage;
use Illuminate\Database\Seeder;

class LegalPageSeeder extends Seeder
{
    public function run(): void
    {
        $pages = [
            [
                'slug' => 'privacy',
                'title' => 'Privacy Policy',
                'last_updated_label' => 'March 2026',
                'enabled' => true,
                'sort' => 1,
                'sections' => [
                    ['section_key' => 'intro', 'title' => '1. Introduction', 'content' => 'SourceNest ("we," "our," or "us") is committed to protecting your privacy. This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you visit our website and use our services.', 'sort' => 1],
                    ['section_key' => 'collect', 'title' => '2. Information We Collect', 'content' => 'We collect information you provide directly to us, such as when you create an account, make a purchase, submit an inquiry, or contact us for support. This may include your name, email address, company name, phone number, and payment information.', 'sort' => 2],
                    ['section_key' => 'use', 'title' => '3. How We Use Your Information', 'content' => 'We use the information we collect to provide, maintain, and improve our services, process transactions, send you technical notices and support messages, and respond to your comments and questions.', 'sort' => 3],
                    ['section_key' => 'security', 'title' => '4. Data Security', 'content' => 'We implement appropriate technical and organizational security measures to protect your personal information against unauthorized access, alteration, disclosure, or destruction.', 'sort' => 4],
                    ['section_key' => 'contact', 'title' => '5. Contact Us', 'content' => 'If you have any questions about this Privacy Policy, please contact us at info@sourcenest.tesh.', 'sort' => 5],
                ],
            ],
            [
                'slug' => 'terms',
                'title' => 'Terms of Service',
                'last_updated_label' => 'March 2026',
                'enabled' => true,
                'sort' => 2,
                'sections' => [
                    ['section_key' => 'acceptance', 'title' => '1. Acceptance of Terms', 'content' => 'By accessing and using SourceNest, you agree to be bound by these Terms of Service and all applicable laws and regulations. If you do not agree with any of these terms, you are prohibited from using or accessing this site.', 'sort' => 1],
                    ['section_key' => 'license', 'title' => '2. Use License', 'content' => 'Permission is granted to temporarily access the materials on SourceNest for personal, non-commercial transitory viewing only. This is the grant of a license, not a transfer of title.', 'sort' => 2],
                    ['section_key' => 'accounts', 'title' => '3. User Accounts', 'content' => 'You are responsible for maintaining the confidentiality of your account and password. You agree to accept responsibility for all activities that occur under your account.', 'sort' => 3],
                    ['section_key' => 'conduct', 'title' => '4. Buyer and Supplier Conduct', 'content' => 'All users agree to conduct business in good faith. Suppliers must provide accurate information about their products and capabilities. Buyers must provide accurate information about their requirements.', 'sort' => 4],
                    ['section_key' => 'liability', 'title' => '5. Limitation of Liability', 'content' => 'SourceNest shall not be liable for any indirect, incidental, special, consequential, or punitive damages resulting from your use of or inability to use the service.', 'sort' => 5],
                    ['section_key' => 'contact', 'title' => '6. Contact Information', 'content' => 'Questions about the Terms of Service should be sent to us at info@sourcenest.tesh.', 'sort' => 6],
                ],
            ],
            [
                'slug' => 'cookies',
                'title' => 'Cookie Policy',
                'last_updated_label' => 'March 2026',
                'enabled' => true,
                'sort' => 3,
                'sections' => [
                    ['section_key' => 'what', 'title' => '1. What Are Cookies', 'content' => 'Cookies are small text files that are placed on your computer or mobile device when you visit a website. They are widely used to make websites work more efficiently and provide information to the owners of the site.', 'sort' => 1],
                    ['section_key' => 'how', 'title' => '2. How We Use Cookies', 'content' => 'We use cookies to understand how you use our website, remember your preferences, and improve your overall experience. We also use cookies to analyze site traffic and for marketing purposes.', 'sort' => 2],
                    ['section_key' => 'types', 'title' => '3. Types of Cookies We Use', 'content' => 'Essential cookies: Required for the website to function properly. Analytics cookies: Help us understand how visitors interact with our website. Marketing cookies: Used to track visitors across websites for advertising purposes.', 'sort' => 3],
                    ['section_key' => 'managing', 'title' => '4. Managing Cookies', 'content' => 'Most web browsers allow you to control cookies through their settings. You can set your browser to refuse cookies or delete certain cookies. However, this may affect the functionality of our website.', 'sort' => 4],
                    ['section_key' => 'contact', 'title' => '5. Contact Us', 'content' => 'If you have any questions about our Cookie Policy, please contact us at info@sourcenest.tesh.', 'sort' => 5],
                ],
            ],
        ];

        foreach ($pages as $pageData) {
            $sections = $pageData['sections'];
            unset($pageData['sections']);

            $page = LegalPage::query()->updateOrCreate(
                ['slug' => $pageData['slug']],
                $pageData,
            );

            $page->upsertTranslations([
                'en' => ['title' => $page->title],
            ]);

            foreach ($sections as $sectionData) {
                $section = $page->sections()->updateOrCreate(
                    ['section_key' => $sectionData['section_key']],
                    [
                        'title' => $sectionData['title'],
                        'content' => $sectionData['content'],
                        'sort' => $sectionData['sort'],
                    ],
                );

                $section->upsertTranslations([
                    'en' => [
                        'title' => $sectionData['title'],
                        'content' => $sectionData['content'],
                    ],
                ]);
            }
        }
    }
}

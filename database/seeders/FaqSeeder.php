<?php

namespace Database\Seeders;

use App\Models\Faq;
use App\Models\FaqCategory;
use Illuminate\Database\Seeder;

class FaqSeeder extends Seeder
{
    /**
     * Seed FAQ entries grouped by category (matches public FAQ page structure).
     */
    public function run(): void
    {
        $categories = [
            'general' => [
                [
                    'question' => 'What is SourceNest?',
                    'answer' => 'SourceNest is a premium global digital marketplace where importers, buyers, and sourcing professionals discover reviewed manufacturers and factories from around the world. Factories present their products, capabilities, and certifications, while buyers search, compare, and connect directly — all in one trusted platform.',
                ],
                [
                    'question' => 'What is an RFQ and how does it work?',
                    'answer' => 'RFQ stands for Request for Quotation. Buyers submit detailed requests with product specifications, quantities, target prices, and delivery timelines. Reviewed manufacturers matching your requirements can respond with competitive quotes directly in your dashboard.',
                ],
                [
                    'question' => 'How do I message a supplier?',
                    'answer' => 'Visit any supplier profile and click "Contact Supplier," or open the messaging center from your dashboard. You can send detailed messages, attach relevant files, and track all conversations in one secure inbox.',
                ],
                [
                    'question' => 'How does the review process work?',
                    'answer' => 'Manufacturers submit business information, factory details, certifications, and profile content. Our team reviews submitted information for quality and transparency before suppliers become visible to buyers on the platform.',
                ],
                [
                    'question' => 'Is SourceNest free for buyers?',
                    'answer' => 'Yes. Buyers use SourceNest completely free — no subscription or hidden fees. Search, compare, message, and request quotes at no cost.',
                ],
                [
                    'question' => 'Why do manufacturers pay to join?',
                    'answer' => 'Manufacturers pay a subscription to maintain a professional profile, upload products, receive inquiries and RFQs, and gain global visibility to active buyers. This model keeps the platform free for buyers while funding quality controls and marketplace operations.',
                ],
            ],
            'for-buyers' => [
                [
                    'question' => 'Is SourceNest free for buyers?',
                    'answer' => 'Yes. SourceNest is free for buyers, forever. There is no subscription, no commission on your deals, and no hidden fees for searching, comparing, messaging, or requesting quotes.',
                ],
                [
                    'question' => 'Do I need an account to use SourceNest?',
                    'answer' => 'You can browse public supplier and product pages without an account. A free buyer account is required to message suppliers, submit RFQs, save favorites, and manage your sourcing activity in the dashboard.',
                ],
                [
                    'question' => 'How do I message a supplier?',
                    'answer' => 'From a supplier profile, click "Contact Supplier" and write a clear message about your requirements. You can attach specifications or designs and manage all replies from your Message Center.',
                ],
                [
                    'question' => 'What is an RFQ and how do I send one?',
                    'answer' => 'Go to "Submit RFQ" from the main menu or your dashboard, describe your product requirements, specify quantity and delivery expectations, attach specifications if needed, and submit. Review incoming quotes from manufacturers in your dashboard.',
                ],
                [
                    'question' => 'How do I compare suppliers?',
                    'answer' => 'Save suppliers using the bookmark or heart icon, then open your dashboard to compare them side-by-side — including capabilities, certifications, products, and other profile details.',
                ],
            ],
            'for-manufacturers' => [
                [
                    'question' => 'Does paying automatically make my profile live?',
                    'answer' => 'No. Payment activates your subscription, but your manufacturer profile must still pass our review and approval process before it becomes visible to buyers. This typically takes 2-5 business days after you submit a complete profile.',
                ],
                [
                    'question' => 'What happens if my profile is not approved?',
                    'answer' => 'If your profile does not meet our requirements, we provide specific feedback on what to update. You can revise and resubmit. If approval is ultimately not possible, a full refund is available within 30 days.',
                ],
                [
                    'question' => 'What subscription plans are available?',
                    'answer' => 'SourceNest offers Starter, Growth, and Enterprise plans with monthly and yearly billing. Plans differ by product limits, analytics, visibility, team users, and support. View current pricing and features on the Pricing page.',
                ],
                [
                    'question' => 'Why do manufacturers need to pay to join?',
                    'answer' => 'Subscription fees support profile hosting, buyer discovery, inquiry and RFQ tools, analytics, and the review process that keeps the marketplace trustworthy. Keeping buyer access free helps manufacturers reach more qualified sourcing professionals.',
                ],
            ],
            'review-trust' => [
                [
                    'question' => 'How long does the review take?',
                    'answer' => 'Profile review typically takes 2-5 business days after you submit a complete manufacturer profile with required information and documents.',
                ],
                [
                    'question' => 'What do the trust badges mean?',
                    'answer' => 'Trust badges indicate that a supplier\'s submitted business information, factory details, certifications, and profile content have been reviewed by SourceNest. They reflect transparency based on submitted information and do not constitute verification or guarantee of any kind.',
                ],
                [
                    'question' => 'What documents are required for the review process?',
                    'answer' => 'Commonly reviewed documents include business registration certificates, export/import licenses where applicable, industry certifications (such as ISO, CE, or FDA), and tax registration documents where applicable. Requirements may vary by industry and region.',
                ],
                [
                    'question' => 'How does the review process work?',
                    'answer' => 'We review company information, factory details, certifications, export experience, and profile content in multiple steps — including document review, factory information review, and profile quality review — before a supplier appears on the platform.',
                ],
            ],
            'messaging-communication' => [
                [
                    'question' => 'Is all communication on the platform?',
                    'answer' => 'SourceNest provides a secure messaging system for buyer–supplier communication. We recommend keeping initial inquiries and negotiations on the platform for accountability and record-keeping.',
                ],
                [
                    'question' => 'Can I share my contact information with suppliers?',
                    'answer' => 'Yes. After establishing contact through the platform, buyers and manufacturers may exchange direct contact details at their discretion. SourceNest facilitates connections but does not take responsibility for off-platform transactions.',
                ],
                [
                    'question' => 'How quickly do suppliers typically respond?',
                    'answer' => 'Response times vary by supplier. Manufacturers are encouraged to respond to new RFQs within 24 hours. Enable notifications in your dashboard to respond quickly when suppliers reply.',
                ],
            ],
            'billing-payments' => [
                [
                    'question' => 'What payment methods do you accept?',
                    'answer' => 'We accept major credit cards (Visa, Mastercard, American Express) and PayPal. Enterprise customers may also use bank transfer.',
                ],
                [
                    'question' => 'Can I change my subscription plan?',
                    'answer' => 'Yes. You can upgrade or downgrade at any time. Upgrades are charged the prorated difference; downgrades take effect at the next billing cycle.',
                ],
                [
                    'question' => 'What is your refund policy?',
                    'answer' => 'If your manufacturer profile cannot be approved after following our feedback process, a full refund is available within 30 days of payment. Contact support for assistance with refund requests.',
                ],
                [
                    'question' => 'Are there any transaction fees or commissions?',
                    'answer' => 'No. SourceNest does not charge commission on deals closed through the platform. Your subscription fee is your only platform cost as a manufacturer.',
                ],
            ],
        ];

        foreach ($categories as $slug => $faqs) {
            $category = FaqCategory::query()->where('slug', $slug)->first();

            if ($category === null) {
                $this->command?->warn("FAQ category \"{$slug}\" not found — run FaqCategorySeeder first.");

                continue;
            }

            $questions = collect($faqs)->pluck('question')->all();
            $category->faqs()->whereNotIn('question', $questions)->delete();

            foreach ($faqs as $index => $faq) {
                Faq::query()->updateOrCreate(
                    [
                        'faq_category_id' => $category->id,
                        'question' => $faq['question'],
                    ],
                    [
                        'answer' => $faq['answer'],
                        'sort' => $index + 1,
                    ],
                );
            }
        }
    }
}

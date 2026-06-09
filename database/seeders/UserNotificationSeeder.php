<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserNotificationSeeder extends Seeder
{
    /**
     * Seed demo in-app notifications (15 per user). Uses direct insert to avoid broadcast events.
     */
    public function run(): void
    {
        $templates = $this->notificationTemplates();

        foreach (User::query()->orderBy('id')->cursor() as $user) {
            $rows = [];
            $base = now();

            foreach ($templates as $index => $template) {
                $createdAt = $base->copy()->subMinutes(30 * (14 - $index));

                $rows[] = [
                    'user_id' => $user->id,
                    'sender_id' => null,
                    'type' => $template['type'],
                    'title' => $template['title'],
                    'body' => $template['body'],
                    'data' => json_encode($template['data']),
                    'action_url' => $template['action_url'] ?? null,
                    'read_at' => ($template['read'] ?? false) ? $createdAt->copy()->addMinutes(5)->toDateTimeString() : null,
                    'created_at' => $createdAt->toDateTimeString(),
                    'updated_at' => $createdAt->toDateTimeString(),
                ];
            }

            DB::table('user_notifications')->insert($rows);
        }
    }

    /**
     * @return list<array{type: string, title: string, body: string, data: array<string, mixed>, action_url?: string, read?: bool}>
     */
    private function notificationTemplates(): array
    {
        return [
            [
                'type' => 'account.security.new_login',
                'title' => 'New sign-in to your account',
                'body' => 'We detected a successful sign-in from a new browser. If this was not you, secure your account immediately.',
                'data' => ['category' => 'security', 'severity' => 'medium'],
                'action_url' => '/account/login-history',
                'read' => true,
            ],
            [
                'type' => 'account.profile.updated',
                'title' => 'Profile details saved',
                'body' => 'Your profile information was updated successfully.',
                'data' => ['category' => 'account'],
                'action_url' => '/me',
                'read' => true,
            ],
            [
                'type' => 'order.status.processing',
                'title' => 'Order is being processed',
                'body' => 'Your order #SN-10482 is now being prepared. You will receive another update when it ships.',
                'data' => ['category' => 'orders', 'order_reference' => 'SN-10482'],
                'action_url' => '/orders',
                'read' => true,
            ],
            [
                'type' => 'order.status.shipped',
                'title' => 'Order has shipped',
                'body' => 'Good news — your package is on the way. Tracking information is available in your orders.',
                'data' => ['category' => 'orders', 'order_reference' => 'SN-10482', 'carrier' => 'Express'],
                'action_url' => '/orders',
                'read' => false,
            ],
            [
                'type' => 'order.status.delivered',
                'title' => 'Delivery completed',
                'body' => 'Your recent order was marked as delivered. Let us know if everything arrived as expected.',
                'data' => ['category' => 'orders', 'order_reference' => 'SN-10301'],
                'action_url' => '/orders',
                'read' => true,
            ],
            [
                'type' => 'catalog.product.back_in_stock',
                'title' => 'Item back in stock',
                'body' => 'A product on your saved list is available again. Quantities may be limited.',
                'data' => ['category' => 'catalog', 'product_slug' => 'industrial-valve-assembly'],
                'action_url' => '/products',
                'read' => false,
            ],
            [
                'type' => 'catalog.price_drop',
                'title' => 'Price drop on a watched product',
                'body' => 'The price on one of your watched items has decreased. View the listing for current pricing.',
                'data' => ['category' => 'catalog', 'percent_change' => -8],
                'action_url' => '/products',
                'read' => true,
            ],
            [
                'type' => 'manufacturer.application.reviewed',
                'title' => 'Manufacturer application update',
                'body' => 'Your factory profile or verification documents were reviewed. Check your dashboard for the latest status.',
                'data' => ['category' => 'manufacturer'],
                'action_url' => '/account',
                'read' => true,
            ],
            [
                'type' => 'support.ticket.reply',
                'title' => 'Support replied to your request',
                'body' => 'Our team added a response to your support conversation. Open the message center to read the full reply.',
                'data' => ['category' => 'support', 'ticket_id' => 'TKT-8891'],
                'action_url' => '/support',
                'read' => false,
            ],
            [
                'type' => 'billing.payment.confirmed',
                'title' => 'Payment received',
                'body' => 'We have confirmed your recent payment. A receipt has been generated for your records.',
                'data' => ['category' => 'billing', 'amount' => '249.00', 'currency' => 'USD'],
                'action_url' => '/billing',
                'read' => true,
            ],
            [
                'type' => 'plan.subscription.renewal_reminder',
                'title' => 'Subscription renews soon',
                'body' => 'Your current plan will renew in the next billing cycle. Update your payment method if needed.',
                'data' => ['category' => 'subscription', 'days_until' => 7],
                'action_url' => '/plans',
                'read' => true,
            ],
            [
                'type' => 'system.announcement.maintenance',
                'title' => 'Scheduled maintenance window',
                'body' => 'We will perform brief infrastructure maintenance. Short interruptions to the API or dashboard may occur.',
                'data' => ['category' => 'system', 'window' => 'Sunday 02:00–04:00 UTC'],
                'action_url' => null,
                'read' => false,
            ],
            [
                'type' => 'compliance.policy.updated',
                'title' => 'Terms or policy update',
                'body' => 'We published updates to our terms of use and privacy practices. Please review the summary in your account.',
                'data' => ['category' => 'legal', 'document' => 'terms_v3'],
                'action_url' => '/legal/terms',
                'read' => true,
            ],
            [
                'type' => 'currency.preference.synced',
                'title' => 'Display currency updated',
                'body' => 'Your preferred display currency is now active for product listings and estimates where supported.',
                'data' => ['category' => 'preferences', 'currency' => 'EUR'],
                'action_url' => '/me/preferences',
                'read' => true,
            ],
            [
                'type' => 'engagement.review.request',
                'title' => 'How was your recent order?',
                'body' => 'Share quick feedback on your last purchase to help other buyers and improve our marketplace.',
                'data' => ['category' => 'engagement', 'order_reference' => 'SN-10301'],
                'action_url' => '/orders',
                'read' => false,
            ],
        ];
    }
}

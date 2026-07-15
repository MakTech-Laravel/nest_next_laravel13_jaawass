<?php

namespace App\Services\Dashboard;

use App\Enums\DashboardEventType;
use App\Enums\UserManuFactureStatus;
use App\Enums\UserStatus;
use App\Models\DashboardEvent;
use App\Models\Product;
use App\Models\RfqSubmission;
use App\Models\User;
use App\Models\UserNotification;

class AdminDashboardService
{
    use BuildsDashboardMetrics;

    /**
     * @return array<string, mixed>
     */
    public function overview(): array
    {
        return [
            'stats' => $this->stats(),
            'pending_approvals' => $this->pendingApprovals(),
            'recent_reports' => $this->recentReports(),
            'recent_activity' => $this->recentActivity(),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function stats(): array
    {
        $now = now();
        $currentMonthStart = $now->copy()->startOfMonth();
        $previousMonthStart = $now->copy()->subMonth()->startOfMonth();
        $previousMonthEnd = $now->copy()->subMonth()->endOfMonth();

        $usersCurrent = User::query()->where('created_at', '>=', $currentMonthStart)->count();
        $usersPrevious = User::query()
            ->whereBetween('created_at', [$previousMonthStart, $previousMonthEnd])
            ->count();
        $totalUsers = User::query()->count();

        $suppliersCurrent = User::query()
            ->isManufacturer()
            ->where('manufacture_status', UserManuFactureStatus::APPROVED->value)
            ->where('status', UserStatus::ACTIVE->value)
            ->where('created_at', '>=', $currentMonthStart)
            ->count();
        $suppliersPrevious = User::query()
            ->isManufacturer()
            ->where('manufacture_status', UserManuFactureStatus::APPROVED->value)
            ->where('status', UserStatus::ACTIVE->value)
            ->whereBetween('created_at', [$previousMonthStart, $previousMonthEnd])
            ->count();
        $activeSuppliers = User::query()
            ->isManufacturer()
            ->where('manufacture_status', UserManuFactureStatus::APPROVED->value)
            ->where('status', UserStatus::ACTIVE->value)
            ->count();

        $productsCurrent = Product::query()->where('created_at', '>=', $currentMonthStart)->count();
        $productsPrevious = Product::query()
            ->whereBetween('created_at', [$previousMonthStart, $previousMonthEnd])
            ->count();
        $productsListed = Product::query()
            ->where('status', 'active')
            ->where('is_approved', true)
            ->count();

        $rfqsCurrent = RfqSubmission::query()->where('created_at', '>=', $currentMonthStart)->count();
        $rfqsPrevious = RfqSubmission::query()
            ->whereBetween('created_at', [$previousMonthStart, $previousMonthEnd])
            ->count();

        $userTrend = $this->metricWithTrend($usersCurrent, $usersPrevious);
        $supplierTrend = $this->metricWithTrend($suppliersCurrent, $suppliersPrevious);
        $productTrend = $this->metricWithTrend($productsCurrent, $productsPrevious);
        $rfqTrend = $this->metricWithTrend($rfqsCurrent, $rfqsPrevious);

        return [
            [
                'key' => 'total_users',
                'label' => 'Total Users',
                'value' => number_format($totalUsers),
                'raw_value' => $totalUsers,
                'change' => $userTrend['change'],
                'trend' => $userTrend['trend'],
            ],
            [
                'key' => 'active_suppliers',
                'label' => 'Active Suppliers',
                'value' => number_format($activeSuppliers),
                'raw_value' => $activeSuppliers,
                'change' => $supplierTrend['change'],
                'trend' => $supplierTrend['trend'],
            ],
            [
                'key' => 'products_listed',
                'label' => 'Products Listed',
                'value' => number_format($productsListed),
                'raw_value' => $productsListed,
                'change' => $productTrend['change'],
                'trend' => $productTrend['trend'],
            ],
            [
                'key' => 'rfqs_this_month',
                'label' => 'RFQs This Month',
                'value' => number_format($rfqsCurrent),
                'raw_value' => $rfqsCurrent,
                'change' => $rfqTrend['change'],
                'trend' => $rfqTrend['trend'],
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function pendingApprovals(int $limit = 5): array
    {
        return User::query()
            ->isManufacturer()
            ->where('manufacture_status', UserManuFactureStatus::PENDING->value)
            ->with(['company.industries'])
            ->latest('manufacture_status_at')
            ->latest('id')
            ->limit($limit)
            ->get()
            ->map(function (User $manufacturer): array {
                $company = $manufacturer->company;
                $industry = $company?->industries?->first()?->name;

                return [
                    'id' => (string) $manufacturer->id,
                    'type' => 'supplier',
                    'name' => $company?->company_name
                        ?: trim("{$manufacturer->first_name} {$manufacturer->last_name}"),
                    'description' => $company?->short_description ?? $company?->long_description ?? '',
                    'country' => $company?->country,
                    'industry' => $industry,
                    'email' => $manufacturer->email,
                    'submitted_at' => $manufacturer->manufacture_status_at?->diffForHumans()
                        ?? $manufacturer->created_at?->diffForHumans(),
                    'submitted_date' => $manufacturer->manufacture_status_at?->format('F j, Y')
                        ?? $manufacturer->created_at?->format('F j, Y'),
                    'status' => 'pending',
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function recentReports(int $limit = 5): array
    {
        return DashboardEvent::query()
            ->whereIn('event_type', [
                DashboardEventType::SupplierRejected->value,
                DashboardEventType::SupplierSuspended->value,
            ])
            ->with(['actor', 'counterparty'])
            ->latest('occurred_at')
            ->limit($limit)
            ->get()
            ->map(function (DashboardEvent $event): array {
                $type = $event->event_type === DashboardEventType::SupplierRejected->value
                    ? 'compliance'
                    : 'risk';

                $subject = $event->counterparty?->company?->company_name
                    ?? $event->counterparty?->email
                    ?? 'Supplier #'.$event->entity_id;

                return [
                    'id' => (string) $event->id,
                    'type' => $type,
                    'subject' => $subject,
                    'reporter' => $event->actor?->email ?? 'system',
                    'reported_at' => $event->occurred_at?->diffForHumans(),
                    'reported_at_iso' => $event->occurred_at?->toIso8601String(),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function recentActivity(int $limit = 5): array
    {
        $activities = DashboardEvent::query()
            ->whereIn('event_type', [
                DashboardEventType::SupplierApproved->value,
                DashboardEventType::SupplierRejected->value,
                DashboardEventType::SupplierSuspended->value,
                DashboardEventType::OrderDelivered->value,
                DashboardEventType::RfqCreated->value,
                DashboardEventType::ReviewSubmitted->value,
            ])
            ->latest('occurred_at')
            ->limit(10)
            ->get()
            ->map(function (DashboardEvent $event): array {
                return [
                    'action' => $this->adminActivityAction($event),
                    'detail' => $this->adminActivityDetail($event),
                    'time' => $event->occurred_at?->diffForHumans(),
                    'time_at' => $event->occurred_at?->toIso8601String(),
                ];
            });

        User::query()
            ->isManufacturer()
            ->with('company')
            ->latest('id')
            ->limit(3)
            ->get()
            ->each(function (User $user) use ($activities): void {
                $name = $user->company?->company_name ?: $user->email;
                $location = collect([$user->company?->city, $user->company?->country])->filter()->implode(', ');

                $activities->push([
                    'action' => 'New supplier registered',
                    'detail' => trim($name.($location ? " from {$location}" : '')),
                    'time' => $user->created_at?->diffForHumans(),
                    'time_at' => $user->created_at?->toIso8601String(),
                ]);
            });

        Product::query()
            ->where('is_approved', true)
            ->latest('updated_at')
            ->limit(2)
            ->get()
            ->each(function (Product $product) use ($activities): void {
                $activities->push([
                    'action' => 'Product approved',
                    'detail' => $product->name,
                    'time' => $product->updated_at?->diffForHumans(),
                    'time_at' => $product->updated_at?->toIso8601String(),
                ]);
            });

        RfqSubmission::query()
            ->where('status', 'accepted')
            ->latest('buyer_action_at')
            ->limit(2)
            ->get()
            ->each(function (RfqSubmission $rfq) use ($activities): void {
                $activities->push([
                    'action' => 'RFQ resolved',
                    'detail' => $rfq->rfq_number.' completed',
                    'time' => ($rfq->buyer_action_at ?? $rfq->updated_at)?->diffForHumans(),
                    'time_at' => ($rfq->buyer_action_at ?? $rfq->updated_at)?->toIso8601String(),
                ]);
            });

        UserNotification::query()
            ->whereNull('sender_id')
            ->latest('id')
            ->limit(2)
            ->get()
            ->each(function (UserNotification $notification) use ($activities): void {
                $activities->push([
                    'action' => $notification->title,
                    'detail' => $notification->body,
                    'time' => $notification->created_at?->diffForHumans(),
                    'time_at' => $notification->created_at?->toIso8601String(),
                ]);
            });

        return $activities
            ->sortByDesc(fn (array $item) => $item['time_at'])
            ->take($limit)
            ->values()
            ->all();
    }

    private function adminActivityAction(DashboardEvent $event): string
    {
        return match ($event->event_type) {
            DashboardEventType::SupplierApproved->value => 'Supplier approved',
            DashboardEventType::SupplierRejected->value => 'Supplier rejected',
            DashboardEventType::SupplierSuspended->value => 'Supplier suspended',
            DashboardEventType::OrderDelivered->value => 'Order completed',
            DashboardEventType::RfqCreated->value => 'New RFQ submitted',
            DashboardEventType::ReviewSubmitted->value => 'New product review',
            default => ucfirst(str_replace('_', ' ', $event->event_type)),
        };
    }

    private function adminActivityDetail(DashboardEvent $event): string
    {
        return match ($event->event_type) {
            DashboardEventType::SupplierApproved->value,
            DashboardEventType::SupplierRejected->value,
            DashboardEventType::SupplierSuspended->value => 'Supplier #'.$event->entity_id,
            DashboardEventType::OrderDelivered->value => 'Order #'.$event->entity_id,
            DashboardEventType::RfqCreated->value => 'RFQ #'.$event->entity_id,
            DashboardEventType::ReviewSubmitted->value => $this->reviewSubmittedDetail($event),
            default => '',
        };
    }

    private function reviewSubmittedDetail(DashboardEvent $event): string
    {
        $metadata = is_array($event->metadata) ? $event->metadata : [];
        $productName = trim((string) ($metadata['product_name'] ?? ''));
        $buyerName = trim((string) ($metadata['buyer_name'] ?? ''));
        $rating = isset($metadata['rating']) ? (int) $metadata['rating'] : null;

        if ($productName !== '' && $buyerName !== '') {
            $detail = "{$productName} by {$buyerName}";

            return $rating !== null && $rating > 0
                ? "{$detail} ({$rating}/5)"
                : $detail;
        }

        return 'Review #'.$event->entity_id;
    }
}

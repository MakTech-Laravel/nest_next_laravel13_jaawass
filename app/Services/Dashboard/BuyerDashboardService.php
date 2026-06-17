<?php

namespace App\Services\Dashboard;

use App\Enums\DashboardEventType;
use App\Enums\RfqSubmissionStatus;
use App\Models\DashboardEvent;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Message;
use App\Models\RfqSubmission;
use App\Models\SaveSupplier;
use App\Models\User;
use App\Services\Supplier\PublicSupplierCatalogService;
use Illuminate\Support\Carbon;

class BuyerDashboardService
{
    use BuildsDashboardMetrics;

    public function __construct(
        private readonly PublicSupplierCatalogService $supplierCatalogService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function overview(User $buyer): array
    {
        return [
            'welcome' => [
                'first_name' => $buyer->first_name,
                'name' => trim("{$buyer->first_name} {$buyer->last_name}"),
            ],
            'stats' => $this->stats($buyer),
            'recent_messages' => $this->recentMessages($buyer),
            'recent_rfqs' => $this->recentRfqs($buyer),
            'recommended_suppliers' => $this->recommendedSuppliers(),
            'recent_activity' => $this->recentActivity($buyer),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function stats(User $buyer): array
    {
        $buyerId = (int) $buyer->id;

        $conversationStats = $this->conversationStats($buyer);
        $rfqCounts = RfqSubmission::query()
            ->where('buyer_id', $buyerId)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $totalRfqs = (int) $rfqCounts->sum();
        $pendingRfqs = (int) ($rfqCounts[RfqSubmissionStatus::Pending->value] ?? 0)
            + (int) ($rfqCounts[RfqSubmissionStatus::InReview->value] ?? 0);

        $savedSuppliers = SaveSupplier::query()->where('user_id', $buyerId)->count();
        $productsViewed = DashboardEvent::query()
            ->where('actor_user_id', $buyerId)
            ->where('event_type', DashboardEventType::ProductViewed->value)
            ->distinct('entity_id')
            ->count('entity_id');

        return [
            'active_conversations' => [
                'value' => $conversationStats['total'],
                'badge' => $conversationStats['unread'] > 0
                    ? '+'.$conversationStats['unread'].' new'
                    : null,
            ],
            'rfqs_submitted' => [
                'value' => $totalRfqs,
                'badge' => $pendingRfqs > 0 ? $pendingRfqs.' pending' : null,
            ],
            'saved_suppliers' => [
                'value' => $savedSuppliers,
                'badge' => null,
            ],
            'products_viewed' => [
                'value' => $productsViewed,
                'badge' => null,
            ],
        ];
    }

    /**
     * @return array{total: int, unread: int}
     */
    private function conversationStats(User $buyer): array
    {
        $userId = (int) $buyer->id;

        $total = Conversation::query()->forParticipant($buyer)->count();

        $unread = (int) Conversation::query()
            ->forParticipant($buyer)
            ->whereRaw(
                '(
                    SELECT MAX(m.id) FROM messages m WHERE m.conversation_id = conversations.id
                ) > COALESCE((
                    SELECT cp.last_read_message_id
                    FROM conversation_participants cp
                    WHERE cp.conversation_id = conversations.id AND cp.user_id = ?
                    LIMIT 1
                ), 0)',
                [$userId]
            )
            ->count();

        return ['total' => $total, 'unread' => $unread];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function recentMessages(User $buyer, int $limit = 3): array
    {
        $userId = (int) $buyer->id;

        $conversations = Conversation::query()
            ->forParticipant($buyer)
            ->with(['participants.company'])
            ->addSelect([
                'last_message_body' => Message::query()
                    ->select('body')
                    ->whereColumn('messages.conversation_id', 'conversations.id')
                    ->orderByDesc('id')
                    ->limit(1),
                'last_message_sent_at' => Message::query()
                    ->select('created_at')
                    ->whereColumn('messages.conversation_id', 'conversations.id')
                    ->orderByDesc('id')
                    ->limit(1),
                'auth_last_read_message_id' => ConversationParticipant::query()
                    ->select('last_read_message_id')
                    ->whereColumn('conversation_participants.conversation_id', 'conversations.id')
                    ->where('conversation_participants.user_id', $userId)
                    ->limit(1),
                'last_message_id' => Message::query()
                    ->select('id')
                    ->whereColumn('messages.conversation_id', 'conversations.id')
                    ->orderByDesc('id')
                    ->limit(1),
            ])
            ->orderByDesc('last_message_sent_at')
            ->limit($limit)
            ->get();

        return $conversations->map(function (Conversation $conversation) use ($userId): array {
            $other = $conversation->participants->firstWhere('id', '!=', $userId);
            $companyName = $other?->company?->company_name;
            $name = $companyName ?: trim(($other?->first_name ?? '').' '.($other?->last_name ?? '')) ?: 'Unknown';
            $lastMessageId = (int) ($conversation->getAttribute('last_message_id') ?? 0);
            $lastReadId = (int) ($conversation->getAttribute('auth_last_read_message_id') ?? 0);
            $sentAt = $conversation->getAttribute('last_message_sent_at');

            return [
                'conversation_id' => $conversation->id,
                'name' => $name,
                'message' => (string) ($conversation->getAttribute('last_message_body') ?? ''),
                'time' => $sentAt ? Carbon::parse($sentAt)->diffForHumans() : null,
                'time_at' => $sentAt,
                'unread' => $lastMessageId > 0 && $lastMessageId > $lastReadId,
            ];
        })->values()->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function recentRfqs(User $buyer, int $limit = 5): array
    {
        return RfqSubmission::query()
            ->where('buyer_id', $buyer->id)
            ->with(['product', 'manufacturer.company'])
            ->latest('id')
            ->limit($limit)
            ->get()
            ->map(function (RfqSubmission $rfq): array {
                $status = $rfq->status instanceof RfqSubmissionStatus
                    ? $rfq->status->value
                    : (string) $rfq->status;

                return [
                    'id' => $rfq->rfq_number,
                    'rfq_id' => $rfq->id,
                    'product' => $rfq->product?->name,
                    'supplier' => $rfq->manufacturer?->company?->company_name,
                    'status' => $this->rfqStatusLabel($status),
                    'status_value' => $status,
                    'date' => $rfq->created_at?->format('M j, Y'),
                    'created_at' => $rfq->created_at?->toIso8601String(),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function recommendedSuppliers(int $limit = 3): array
    {
        return $this->supplierCatalogService
            ->publicSupplierBaseQuery()
            ->with($this->supplierCatalogService->eagerRelationsForList())
            ->withCount('manufacturerReviews as review_count')
            ->withAvg('manufacturerReviews as avg_rating', 'rating')
            ->withCount([
                'products as public_product_count' => fn ($q) => $q
                    ->where('status', 'active')
                    ->where('is_approved', true),
            ])
            ->inRandomOrder()
            ->limit($limit)
            ->get()
            ->map(function (User $supplier): array {
                $company = $supplier->company;

                return [
                    'id' => $supplier->id,
                    'name' => $company?->company_name,
                    'slug' => $company?->slug,
                    'location' => [
                        'city' => $company?->city,
                        'country' => $company?->country,
                    ],
                    'rating' => $supplier->avg_rating !== null
                        ? round((float) $supplier->avg_rating, 1)
                        : 0.0,
                    'product_count' => (int) ($supplier->public_product_count ?? 0),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function recentActivity(User $buyer, int $limit = 8): array
    {
        return DashboardEvent::query()
            ->where('actor_user_id', $buyer->id)
            ->whereIn('event_type', [
                DashboardEventType::ProductViewed->value,
                DashboardEventType::SupplierViewed->value,
                DashboardEventType::ProductSaved->value,
                DashboardEventType::SupplierSaved->value,
                DashboardEventType::RfqCreated->value,
                DashboardEventType::MessageSent->value,
            ])
            ->latest('occurred_at')
            ->limit($limit)
            ->get()
            ->map(function (DashboardEvent $event): array {
                return [
                    'id' => $event->id,
                    'type' => $event->event_type,
                    'title' => $this->buyerActivityTitle($event),
                    'description' => $this->buyerActivityDescription($event),
                    'time' => $event->occurred_at?->diffForHumans(),
                    'time_at' => $event->occurred_at?->toIso8601String(),
                ];
            })
            ->values()
            ->all();
    }

    private function buyerActivityTitle(DashboardEvent $event): string
    {
        return match ($event->event_type) {
            DashboardEventType::ProductViewed->value => 'Viewed product',
            DashboardEventType::SupplierViewed->value => 'Viewed supplier profile',
            DashboardEventType::ProductSaved->value => 'Saved product',
            DashboardEventType::SupplierSaved->value => 'Saved supplier',
            DashboardEventType::RfqCreated->value => 'Submitted RFQ',
            DashboardEventType::MessageSent->value => 'Sent message',
            default => 'Activity',
        };
    }

    private function buyerActivityDescription(DashboardEvent $event): string
    {
        return match ($event->event_type) {
            DashboardEventType::ProductViewed->value => 'Product #'.$event->entity_id,
            DashboardEventType::SupplierViewed->value => 'Supplier #'.$event->entity_id,
            DashboardEventType::ProductSaved->value => 'Product #'.$event->entity_id,
            DashboardEventType::SupplierSaved->value => 'Supplier #'.$event->entity_id,
            DashboardEventType::RfqCreated->value => 'RFQ #'.$event->entity_id,
            DashboardEventType::MessageSent->value => 'Conversation #'.$event->entity_id,
            default => '',
        };
    }
}

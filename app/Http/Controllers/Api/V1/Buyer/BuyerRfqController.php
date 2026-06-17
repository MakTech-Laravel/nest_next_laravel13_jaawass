<?php

namespace App\Http\Controllers\Api\V1\Buyer;

use App\Enums\DashboardEventType;
use App\Enums\RfqSubmissionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Buyer\RespondToRfqQuoteRequest;
use App\Http\Requests\Api\V1\Buyer\StoreRfqSubmissionRequest;
use App\Http\Requests\Api\V1\Buyer\UpdateRfqStatusRequest;
use App\Http\Resources\Api\V1\Buyer\RfqSubmissionResource;
use App\Models\Product;
use App\Models\RfqSubmission;
use App\Services\Dashboard\EventTrackerService;
use App\Services\Buyer\RfqSubmissionService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response as HttpStatus;

class BuyerRfqController extends Controller
{
    public function __construct(
        private readonly RfqSubmissionService $rfqSubmissionService,
        private readonly EventTrackerService $eventTracker,
    ) {}

    public function store(StoreRfqSubmissionRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $product = Product::query()
            ->with('user')
            ->findOrFail((int) $validated['product_id']);

        if ((int) $product->user_id === (int) $request->user()->id) {
            return sendResponse(
                status: false,
                message: __('api.rfq_own_product_not_allowed'),
                data: null,
                statusCode: HttpStatus::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $rfq = $this->rfqSubmissionService->submit($request->user(), $product, $validated);

        $this->eventTracker->track(
            eventType: DashboardEventType::RfqCreated,
            actor: $request->user(),
            entityType: 'rfq_submission',
            entityId: (int) $rfq->id,
            counterparty: $product->user,
            metadata: [
                'product_id' => (int) $product->id,
                'manufacturer_id' => (int) $product->user_id,
            ],
            occurredAt: $rfq->created_at,
        );

        return sendResponse(
            status: true,
            message: __('api.rfq_submitted_successfully'),
            data: new RfqSubmissionResource($rfq),
            statusCode: HttpStatus::HTTP_CREATED
        );
    }

    public function index(Request $request): JsonResponse
    {
        $this->markExpiredQuotes();
        $validated = $this->validatedListFilters($request);
        $rfqs = $this->rfqListQuery((int) $request->user()->id, $validated)
            ->paginate((int) ($validated['per_page'] ?? 15))
            ->withQueryString();

        return sendResponse(
            status: true,
            message: __('api.rfqs_fetched_successfully'),
            data: RfqSubmissionResource::collection($rfqs),
            statusCode: HttpStatus::HTTP_OK
        );
    }

    public function search(Request $request): JsonResponse
    {
        $this->markExpiredQuotes();
        $validated = $this->validatedListFilters($request);
        $rfqs = $this->rfqListQuery((int) $request->user()->id, $validated)
            ->paginate((int) ($validated['per_page'] ?? 15))
            ->withQueryString();

        return sendResponse(
            status: true,
            message: __('api.rfqs_fetched_successfully'),
            data: RfqSubmissionResource::collection($rfqs),
            statusCode: HttpStatus::HTTP_OK
        );
    }

    public function show(Request $request, int $rfq): JsonResponse
    {
        $this->markExpiredQuotes();
        $rfqSubmission = RfqSubmission::query()
            ->where('buyer_id', $request->user()->id)
            ->with([
                'product',
                'manufacturer.company',
                'conversation',
                'quoteAttachments',
            ])
            ->findOrFail($rfq);

        return sendResponse(
            status: true,
            message: __('api.rfq_fetched_successfully'),
            data: new RfqSubmissionResource($rfqSubmission),
            statusCode: HttpStatus::HTTP_OK
        );
    }

    public function updateStatus(UpdateRfqStatusRequest $request, int $rfq): JsonResponse
    {
        $rfqSubmission = RfqSubmission::query()
            ->where('buyer_id', $request->user()->id)
            ->with([
                'product',
                'manufacturer.company',
                'conversation',
                'quoteAttachments',
            ])
            ->findOrFail($rfq);

        $rfqSubmission->forceFill([
            'status' => (string) $request->validated('status'),
        ])->save();

        return sendResponse(
            status: true,
            message: __('api.rfq_status_updated_successfully'),
            data: new RfqSubmissionResource($rfqSubmission),
            statusCode: HttpStatus::HTTP_OK
        );
    }

    public function counts(Request $request): JsonResponse
    {
        $this->markExpiredQuotes();
        $buyerId = (int) $request->user()->id;

        $statusCounts = RfqSubmission::query()
            ->where('buyer_id', $buyerId)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $pendingCount = (int) ($statusCounts[RfqSubmissionStatus::Pending->value] ?? 0);
        $inReviewCount = (int) ($statusCounts[RfqSubmissionStatus::InReview->value] ?? 0);
        $quotedCount = (int) ($statusCounts[RfqSubmissionStatus::Quoted->value] ?? 0);
        $acceptedCount = (int) ($statusCounts[RfqSubmissionStatus::Accepted->value] ?? 0);
        $cancelledCount = (int) ($statusCounts[RfqSubmissionStatus::Cancelled->value] ?? 0);
        $expiredCount = (int) ($statusCounts[RfqSubmissionStatus::Expired->value] ?? 0);

        return sendResponse(
            status: true,
            message: __('api.rfq_counts_fetched_successfully'),
            data: [
                'total_rfqs' => (int) $statusCounts->sum(),
                'quoted' => $quotedCount,
                'pending' => $pendingCount,
                'in_review' => $inReviewCount,
                'accepted' => $acceptedCount,
                'cancelled' => $cancelledCount,
                'expired' => $expiredCount,
            ],
            statusCode: HttpStatus::HTTP_OK
        );
    }

    public function respondToQuote(RespondToRfqQuoteRequest $request, int $rfq): JsonResponse
    {
        $this->markExpiredQuotes();

        $rfqSubmission = RfqSubmission::query()
            ->where('buyer_id', $request->user()->id)
            ->with([
                'product',
                'manufacturer.company',
                'conversation',
                'quoteAttachments',
            ])
            ->findOrFail($rfq);

        if ($rfqSubmission->status !== RfqSubmissionStatus::Quoted) {
            return sendResponse(
                status: false,
                message: __('api.rfq_quote_response_invalid_status'),
                data: null,
                statusCode: HttpStatus::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $status = $request->validated('action') === 'accept'
            ? RfqSubmissionStatus::Accepted
            : RfqSubmissionStatus::Cancelled;

        $rfqSubmission->forceFill([
            'status' => $status->value,
            'buyer_action_at' => now(),
        ])->save();

        return sendResponse(
            status: true,
            message: __('api.rfq_quote_response_saved_successfully'),
            data: new RfqSubmissionResource($rfqSubmission->fresh(['product', 'manufacturer.company', 'conversation'])),
            statusCode: HttpStatus::HTTP_OK
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedListFilters(Request $request): array
    {
        return $request->validate([
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'search' => ['sometimes', 'string', 'max:120'],
            'status' => ['sometimes', 'string', Rule::in(RfqSubmissionStatus::values())],
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function rfqListQuery(int $buyerId, array $validated): Builder
    {
        $query = RfqSubmission::query()
            ->where('buyer_id', $buyerId)
            ->with([
                'product',
                'manufacturer.company',
                'conversation',
                'quoteAttachments',
            ])
            ->latest('id');

        if (isset($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (! empty($validated['search'])) {
            $searchTerm = trim((string) $validated['search']);
            $query->where(function (Builder $builder) use ($searchTerm): void {
                $builder
                    ->where('rfq_number', 'like', "%{$searchTerm}%")
                    ->orWhereHas('product', function (Builder $productQuery) use ($searchTerm): void {
                        $productQuery
                            ->where('name', 'like', "%{$searchTerm}%")
                            ->orWhere('slug', 'like', "%{$searchTerm}%");
                    })
                    ->orWhereHas('manufacturer.company', function (Builder $companyQuery) use ($searchTerm): void {
                        $companyQuery->where('company_name', 'like', "%{$searchTerm}%");
                    });
            });
        }

        return $query;
    }

    private function markExpiredQuotes(): void
    {
        RfqSubmission::query()
            ->where('status', RfqSubmissionStatus::Quoted->value)
            ->whereDate('quote_valid_until', '<', now()->toDateString())
            ->update(['status' => RfqSubmissionStatus::Expired->value]);
    }
}

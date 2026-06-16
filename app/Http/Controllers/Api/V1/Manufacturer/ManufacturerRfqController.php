<?php

namespace App\Http\Controllers\Api\V1\Manufacturer;

use App\Enums\RfqSubmissionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Manufacturer\ReplyToRfqRequest;
use App\Http\Requests\Api\V1\Manufacturer\SendRfqQuoteRequest;
use App\Http\Resources\Api\V1\Manufacturer\RfqSubmissionResource;
use App\Models\RfqSubmission;
use App\Services\Manufacturer\RfqQuoteService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response as HttpStatus;

class ManufacturerRfqController extends Controller
{
    public function __construct(
        private readonly RfqQuoteService $rfqQuoteService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->markExpiredQuotes();

        $validated = $request->validate([
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'search' => ['sometimes', 'string', 'max:120'],
            'status' => ['sometimes', 'string', Rule::in(RfqSubmissionStatus::values())],
        ]);

        $query = RfqSubmission::query()
            ->where('manufacturer_id', $request->user()->id)
            ->with(['buyer', 'product', 'conversation', 'quoteAttachments'])
            ->latest('id');

        if (isset($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (! empty($validated['search'])) {
            $searchTerm = trim((string) $validated['search']);
            $query->where(function (Builder $builder) use ($searchTerm): void {
                $builder
                    ->where('rfq_number', 'like', "%{$searchTerm}%")
                    ->orWhereHas('buyer', function (Builder $buyerQuery) use ($searchTerm): void {
                        $buyerQuery
                            ->where('first_name', 'like', "%{$searchTerm}%")
                            ->orWhere('last_name', 'like', "%{$searchTerm}%")
                            ->orWhere('email', 'like', "%{$searchTerm}%");
                    })
                    ->orWhereHas('product', function (Builder $productQuery) use ($searchTerm): void {
                        $productQuery->where('name', 'like', "%{$searchTerm}%");
                    });
            });
        }

        $rfqs = $query->paginate((int) ($validated['per_page'] ?? 15))->withQueryString();

        return sendResponse(
            status: true,
            message: __('api.manufacturer_rfqs_fetched_successfully'),
            data: RfqSubmissionResource::collection($rfqs),
            statusCode: HttpStatus::HTTP_OK
        );
    }

    public function show(Request $request, int $rfq): JsonResponse
    {
        $this->markExpiredQuotes();

        $rfqSubmission = $this->manufacturerRfqQuery((int) $request->user()->id)
            ->findOrFail($rfq);

        return sendResponse(
            status: true,
            message: __('api.manufacturer_rfq_fetched_successfully'),
            data: new RfqSubmissionResource($rfqSubmission),
            statusCode: HttpStatus::HTTP_OK
        );
    }

    public function reply(ReplyToRfqRequest $request, int $rfq): JsonResponse
    {
        $rfqSubmission = $this->manufacturerRfqQuery((int) $request->user()->id)
            ->findOrFail($rfq);

        $rfqSubmission->forceFill([
            'manufacturer_reply' => $request->validated('manufacturer_reply'),
            'status' => $rfqSubmission->status === RfqSubmissionStatus::Pending
                ? RfqSubmissionStatus::InReview->value
                : $rfqSubmission->status->value,
        ])->save();

        return sendResponse(
            status: true,
            message: __('api.manufacturer_rfq_replied_successfully'),
            data: new RfqSubmissionResource($rfqSubmission->fresh(['buyer', 'product', 'conversation'])),
            statusCode: HttpStatus::HTTP_OK
        );
    }

    public function sendQuote(SendRfqQuoteRequest $request, int $rfq): JsonResponse
    {
        $rfqSubmission = $this->manufacturerRfqQuery((int) $request->user()->id)
            ->findOrFail($rfq);

        $rfqSubmission = $this->rfqQuoteService->sendQuote($rfqSubmission, $request);

        return sendResponse(
            status: true,
            message: __('api.manufacturer_rfq_quoted_successfully'),
            data: new RfqSubmissionResource($rfqSubmission),
            statusCode: HttpStatus::HTTP_OK
        );
    }

    public function counts(Request $request): JsonResponse
    {
        $this->markExpiredQuotes();

        $statusCounts = RfqSubmission::query()
            ->where('manufacturer_id', $request->user()->id)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return sendResponse(
            status: true,
            message: __('api.manufacturer_rfq_counts_fetched_successfully'),
            data: [
                'total_rfqs' => (int) $statusCounts->sum(),
                'new' => (int) ($statusCounts[RfqSubmissionStatus::Pending->value] ?? 0),
                'in_review' => (int) ($statusCounts[RfqSubmissionStatus::InReview->value] ?? 0),
                'quoted' => (int) ($statusCounts[RfqSubmissionStatus::Quoted->value] ?? 0),
                'accepted' => (int) ($statusCounts[RfqSubmissionStatus::Accepted->value] ?? 0),
                'cancelled' => (int) ($statusCounts[RfqSubmissionStatus::Cancelled->value] ?? 0),
                'expired' => (int) ($statusCounts[RfqSubmissionStatus::Expired->value] ?? 0),
            ],
            statusCode: HttpStatus::HTTP_OK
        );
    }

    private function manufacturerRfqQuery(int $manufacturerId): Builder
    {
        return RfqSubmission::query()
            ->where('manufacturer_id', $manufacturerId)
            ->with(['buyer', 'product', 'conversation', 'quoteAttachments']);
    }

    private function markExpiredQuotes(): void
    {
        RfqSubmission::query()
            ->where('status', RfqSubmissionStatus::Quoted->value)
            ->whereDate('quote_valid_until', '<', now()->toDateString())
            ->update(['status' => RfqSubmissionStatus::Expired->value]);
    }
}

<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\PromotionUserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\EnrollPromotionRequest;
use App\Http\Requests\Api\V1\Admin\UpdatePromotionParticipantRequest;
use App\Http\Requests\Api\V1\Admin\UpdatePromotionRequest;
use App\Http\Resources\Api\V1\Admin\PromotionParticipantResource;
use App\Http\Resources\Api\V1\Admin\PromotionResource;
use App\Models\Promotion;
use App\Models\User;
use App\Services\Promotion\PromotionService;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response as HttpStatus;

class PromotionController extends Controller
{
    public function __construct(
        private readonly PromotionService $promotionService,
    ) {}

    public function index(Request $request)
    {
        $query = Promotion::query()
            ->with('plan')
            ->withCount([
                'users as accepted_count' => fn ($q) => $q->where('promotion_user.status', PromotionUserStatus::ACCEPTED->value),
                'users as pending_count' => fn ($q) => $q->where('promotion_user.status', PromotionUserStatus::PENDING->value),
                'users as rejected_count' => fn ($q) => $q->where('promotion_user.status', PromotionUserStatus::REJECTED->value),
                'users as total_participants_count',
            ])
            ->latest('id');

        if ($request->boolean('only_active')) {
            $query->where('status', true);
        }

        $promotions = $query->paginate(
            perPage: $request->integer('per_page', 15),
            pageName: 'page',
            page: $request->integer('page', 1),
        );

        return sendResponse(
            status: true,
            message: __('common.success'),
            data: PromotionResource::collection($promotions),
            statusCode: HttpStatus::HTTP_OK,
        );
    }

    public function active()
    {
        $promotion = $this->promotionService->findActivePromotion();

        if (! $promotion) {
            return sendResponse(
                status: false,
                message: __('promotion.no_active_promotion'),
                data: null,
                statusCode: HttpStatus::HTTP_NOT_FOUND,
            );
        }

        return sendResponse(
            status: true,
            message: __('common.success'),
            data: new PromotionResource($promotion),
            statusCode: HttpStatus::HTTP_OK,
        );
    }

    public function show(Request $request, Promotion $promotion)
    {
        $promotion->load('plan');

        $participants = $promotion->users()
            ->with('company')
            ->when(
                $request->filled('status'),
                fn ($q) => $q->where('promotion_user.status', request()->string('status')->toString()),
            )
            ->orderByDesc('promotion_user.participated_at')
            ->paginate(
                perPage: request()->integer('per_page', 15),
                pageName: 'page',
                page: request()->integer('page', 1),
            );

        return sendResponse(
            status: true,
            message: __('common.success'),
            data: [
                'promotion' => new PromotionResource($promotion),
                'participants' => PromotionParticipantResource::collection($participants),
            ],
            statusCode: HttpStatus::HTTP_OK,
        );
    }

    public function update(UpdatePromotionRequest $request, Promotion $promotion)
    {
        $validated = $request->validated();
        $locale = $validated['locale'] ?? $request->query('locale') ?? app()->getLocale();
        unset($validated['locale']);

        if (isset($validated['billing_period_unit'])) {
            $validated['billing_period_unit'] = match (strtolower((string) $validated['billing_period_unit'])) {
                'monthly', 'month' => 'month',
                'yearly', 'year' => 'year',
                default => $validated['billing_period_unit'],
            };
        }

        $promotion->update($validated);

        if ($request->boolean('status')) {
            $this->promotionService->ensureSingleActive($promotion);
        }

        $translatableChanged = array_intersect_key(
            $validated,
            array_flip($promotion->translatableFields()),
        );

        if (! empty($translatableChanged)) {
            $promotion->upsertTranslations([
                $locale => $translatableChanged,
            ]);

            $promotion->autoTranslate(
                sourceData: $translatableChanged,
                sourceLocale: $locale,
            );
        }

        $promotion->load('plan');

        return sendResponse(
            status: true,
            message: __('common.updated'),
            data: new PromotionResource($promotion->fresh()),
            statusCode: HttpStatus::HTTP_OK,
        );
    }

    public function toggleStatus(Promotion $promotion)
    {
        $promotion->update(['status' => ! $promotion->status]);

        if ($promotion->status) {
            $this->promotionService->ensureSingleActive($promotion);
        }

        $promotion->load('plan');

        return sendResponse(
            status: true,
            message: __('common.status_toggled'),
            data: new PromotionResource($promotion->fresh()),
            statusCode: HttpStatus::HTTP_OK,
        );
    }

    public function reset()
    {
        $promotion = $this->promotionService->reset();

        $promotions = Promotion::query()
            ->with('plan')
            ->withCount([
                'users as accepted_count' => fn ($q) => $q->where('promotion_user.status', PromotionUserStatus::ACCEPTED->value),
                'users as pending_count' => fn ($q) => $q->where('promotion_user.status', PromotionUserStatus::PENDING->value),
                'users as rejected_count' => fn ($q) => $q->where('promotion_user.status', PromotionUserStatus::REJECTED->value),
                'users as total_participants_count',
            ])
            ->latest('id')
            ->get();

        return sendResponse(
            status: true,
            message: __('promotion.reset_success'),
            data: [
                'promotion' => new PromotionResource($promotion),
                'promotions' => PromotionResource::collection($promotions),
            ],
            statusCode: HttpStatus::HTTP_CREATED,
        );
    }

    public function enroll(EnrollPromotionRequest $request, Promotion $promotion)
    {
        $user = User::query()->findOrFail($request->integer('user_id'));

        try {
            $this->promotionService->assertManufacturer($user);

            $status = $request->filled('status')
                ? PromotionUserStatus::from($request->string('status')->toString())
                : PromotionUserStatus::PENDING;

            $this->promotionService->enroll($promotion, $user, $status);
        } catch (InvalidArgumentException $exception) {
            return sendResponse(
                status: false,
                message: $exception->getMessage(),
                data: null,
                statusCode: HttpStatus::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        $participant = $promotion->users()
            ->with('company')
            ->where('user_id', $user->id)
            ->first();

        return sendResponse(
            status: true,
            message: __('common.created'),
            data: new PromotionParticipantResource($participant),
            statusCode: HttpStatus::HTTP_CREATED,
        );
    }

    public function updateParticipantStatus(
        UpdatePromotionParticipantRequest $request,
        Promotion $promotion,
        User $user,
    ) {
        try {
            $this->promotionService->updateParticipantStatus(
                $promotion,
                $user,
                PromotionUserStatus::from($request->string('status')->toString()),
            );
        } catch (InvalidArgumentException $exception) {
            return sendResponse(
                status: false,
                message: $exception->getMessage(),
                data: null,
                statusCode: HttpStatus::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        $participant = $promotion->users()
            ->with('company')
            ->where('user_id', $user->id)
            ->first();

        return sendResponse(
            status: true,
            message: __('common.updated'),
            data: new PromotionParticipantResource($participant),
            statusCode: HttpStatus::HTTP_OK,
        );
    }

    public function participants(Request $request, Promotion $promotion)
    {
        $participants = $promotion->users()
            ->with('company')
            ->when(
                $request->filled('status'),
                fn ($q) => $q->where('promotion_user.status', $request->string('status')->toString()),
            )
            ->orderByDesc('promotion_user.participated_at')
            ->paginate(
                perPage: $request->integer('per_page', 15),
                pageName: 'page',
                page: $request->integer('page', 1),
            );

        return sendResponse(
            status: true,
            message: __('common.success'),
            data: PromotionParticipantResource::collection($participants),
            statusCode: HttpStatus::HTTP_OK,
        );
    }
}

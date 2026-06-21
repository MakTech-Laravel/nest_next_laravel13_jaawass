<?php

namespace App\Http\Controllers\Api\V1\Manufacturer;

use App\Enums\PromotionUserStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Admin\PromotionParticipantResource;
use App\Http\Resources\Api\V1\Admin\PromotionResource;
use App\Models\Promotion;
use App\Services\Promotion\PromotionService;
use App\Services\Subscription\PlanEntitlementResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response as HttpStatus;

class ManufacturerPromotionController extends Controller
{
    public function __construct(
        private readonly PromotionService $promotionService,
        private readonly PlanEntitlementResolver $entitlementResolver,
    ) {}

    public function active(): JsonResponse
    {
        $promotion = $this->promotionService->findActivePromotion();

        if ($promotion === null) {
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

    public function myApplication(Request $request): JsonResponse
    {
        $promotionId = $request->integer('promotion_id');

        $promotion = $promotionId > 0
            ? Promotion::query()->find($promotionId)
            : $this->promotionService->findActivePromotion();

        if ($promotion === null) {
            return sendResponse(
                status: false,
                message: __('promotion.no_active_promotion'),
                data: null,
                statusCode: HttpStatus::HTTP_NOT_FOUND,
            );
        }

        $participant = $promotion->users()
            ->with('company')
            ->where('user_id', $request->user()->id)
            ->first();

        if ($participant === null) {
            return sendResponse(
                status: true,
                message: __('promotion.not_applied'),
                data: null,
                statusCode: HttpStatus::HTTP_OK,
            );
        }

        return sendResponse(
            status: true,
            message: __('common.success'),
            data: [
                'promotion' => new PromotionResource($promotion->load('plan.planFeatures.feature')),
                'application' => new PromotionParticipantResource($participant),
            ],
            statusCode: HttpStatus::HTTP_OK,
        );
    }

    public function apply(Request $request): JsonResponse
    {
        $promotion = $request->filled('promotion_id')
            ? Promotion::query()->find($request->integer('promotion_id'))
            : $this->promotionService->findActivePromotion();

        if ($promotion === null || ! $promotion->status) {
            return sendResponse(
                status: false,
                message: __('promotion.no_active_promotion'),
                data: null,
                statusCode: HttpStatus::HTTP_NOT_FOUND,
            );
        }

        if ($this->promotionService->enrollmentStats($promotion)['is_full']) {
            return sendResponse(
                status: false,
                message: __('promotion.full'),
                data: null,
                statusCode: HttpStatus::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        if ($this->entitlementResolver->for($request->user())->hasActiveSubscription()) {
            return sendResponse(
                status: false,
                message: __('promotion.already_has_subscription'),
                data: null,
                statusCode: HttpStatus::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        try {
            $this->promotionService->enroll(
                $promotion,
                $request->user(),
                PromotionUserStatus::PENDING,
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
            ->where('user_id', $request->user()->id)
            ->first();

        return sendResponse(
            status: true,
            message: __('promotion.application_submitted'),
            data: [
                'promotion' => new PromotionResource($promotion->load('plan.planFeatures.feature')),
                'application' => new PromotionParticipantResource($participant),
            ],
            statusCode: HttpStatus::HTTP_CREATED,
        );
    }
}

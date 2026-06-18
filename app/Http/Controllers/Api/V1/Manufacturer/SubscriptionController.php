<?php

namespace App\Http\Controllers\Api\V1\Manufacturer;

use App\Exceptions\Payment\PaymentVerificationException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Subscription\Manufacturer\SubcriptionUpdgradeRquest;
use App\Http\Requests\Api\V1\Subscription\Manufacturer\SubscriptionStoreRequest;
use App\Http\Resources\Api\V1\SubscriptionResource;
use App\Models\Payment;
use App\Services\Subscription\SubscriptionPurchaseService;
use App\Services\Subscription\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response as HttpStatus;

class SubscriptionController extends Controller
{
    public function __construct(
        private readonly SubscriptionPurchaseService $purchaseService,
        private readonly SubscriptionService $subscriptionService,
    ) {}

    public function subscribe(SubscriptionStoreRequest $request)
    {
        $manufacturer = $request->user();
        $validated = $request->validated();

        $existingPayment = Payment::query()
            ->where('payment_id', $validated['payment_id'])
            ->first();

        if ($existingPayment !== null && (int) $existingPayment->user_id === (int) $manufacturer->id) {
            $subscription = $manufacturer->subscription()->with('plan')->first();

            if ($subscription !== null) {
                return sendResponse(
                    status: true,
                    message: __('subscription.subscription_already_recorded'),
                    data: new SubscriptionResource($subscription),
                    statusCode: HttpStatus::HTTP_OK,
                );
            }
        }

        $manufacturer->load('subscription');

        if ($manufacturer->subscription) {
            return sendResponse(
                status: false,
                message: __('subscription.already_subscribed'),
                data: new SubscriptionResource($manufacturer->subscription->load('plan')),
                statusCode: HttpStatus::HTTP_CONFLICT,
            );
        }

        try {
            $result = $this->purchaseService->confirmPurchase(
                $manufacturer,
                $validated,
            );
        } catch (PaymentVerificationException $exception) {
            return sendResponse(
                status: false,
                message: $exception->getMessage(),
                data: null,
                statusCode: $exception->statusCode,
            );
        } catch (ValidationException $exception) {
            throw $exception;
        }

        return sendResponse(
            status: true,
            message: $result['created']
                ? __('subscription.subscription_created')
                : __('subscription.subscription_already_recorded'),
            data: new SubscriptionResource($result['subscription']),
            statusCode: $result['created'] ? HttpStatus::HTTP_CREATED : HttpStatus::HTTP_OK,
        );
    }

    public function show(Request $request)
    {
        $subscription = $this->subscriptionService->getSubscriptionByManufacturerId($request->user()->id);

        if ($subscription === null) {
            return sendResponse(
                status: false,
                message: __('common.not_found'),
                data: null,
                statusCode: HttpStatus::HTTP_NOT_FOUND,
            );
        }

        $subscription->load('manufacturer', 'plan');

        return sendResponse(
            status: true,
            message: __('common.success'),
            data: new SubscriptionResource($subscription),
            statusCode: HttpStatus::HTTP_OK,
        );
    }

    public function cancel(Request $request)
    {
        try {
            $subscription = $this->purchaseService->cancelPurchase($request->user());
        } catch (ValidationException $exception) {
            throw $exception;
        }

        return sendResponse(
            status: true,
            message: __('common.cancel_subscription'),
            data: new SubscriptionResource($subscription),
            statusCode: HttpStatus::HTTP_OK,
        );
    }

    public function upgrade(SubcriptionUpdgradeRquest $request)
    {
        try {
            $subscription = $this->purchaseService->upgradePurchase(
                $request->user(),
                $request->validated(),
            );
        } catch (PaymentVerificationException $exception) {
            return sendResponse(
                status: false,
                message: $exception->getMessage(),
                data: null,
                statusCode: $exception->statusCode,
            );
        } catch (ValidationException $exception) {
            throw $exception;
        }

        return sendResponse(
            status: true,
            message: __('subscription.subscription_updated'),
            data: new SubscriptionResource($subscription),
            statusCode: HttpStatus::HTTP_OK,
        );
    }
}

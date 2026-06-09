<?php

namespace App\Http\Controllers\Api\V1\Manufacturer;

use App\Enums\Api\V1\SubscriptionEventType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Subscription\Manufacturer\SubcriptionUpdgradeRquest;
use App\Http\Requests\Api\V1\Subscription\Manufacturer\SubscriptionStoreRequest;
use App\Http\Resources\Api\V1\SubscriptionResource;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\User;
use App\Services\Payment\PaymentCheckService;
use App\Services\Subscription\SubscriptionLogService;
use App\Services\Subscription\SubscriptionService;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\Response as HttpStatus;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    //
    public function __construct(
        private PaymentCheckService $paymentCheckService,
        private SubscriptionService $subscriptionService,
        private SubscriptionLogService $subscriptionLogService
    ) {}

    public function subscribe(SubscriptionStoreRequest $request)
    {

        $manufacturer = $request->user();

        $manufacturer->load('subscription');

        if ($manufacturer->subscription) {
            return sendResponse(
                status: false,
                message: __('subscription.already_subscribed'),
                data: new SubscriptionResource($manufacturer->subscription),
                statusCode: HttpStatus::HTTP_NOT_FOUND
            );
        }

        $validated = $request->validated();
        
        $this->paymentCheckService->checkPayment( $validated['payment_method'], $validated );

      
       


        try {
            Payment::create([
                'payment_id' => $validated['payment_id'],
                'payment_method' => $validated['payment_method'],
                'amount' => $validated['paid_amount'],
                'status' => 'paid',
                'source_id' => $validated['plan_id'],
                'user_id' => $validated['manufacturer_id'],
                'source_type' => Plan::class,
            ]);
        } catch (\Exception $e) {
            return sendResponse(
                status: false,
                message: $e->getMessage(),
                data: null,
                statusCode: HttpStatus::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        if (isset($validated['starts_at'])) {
            $validated['starts_at'] = Carbon::parse($validated['starts_at']);
        }
        if (isset($validated['ends_at'])) {
            $validated['ends_at'] = Carbon::parse($validated['ends_at']);
        }
        if (isset($validated['trial_ends_at'])) {
            $validated['trial_ends_at'] = Carbon::parse($validated['trial_ends_at']);
        }

        $subscription = $this->subscriptionService->createSubscription($validated);

        $subscription->load('manufacturer', 'plan');

        if ($subscription) {
            $this->subscriptionLogService->createSubscriptionLog([
                'manufacturer_id' => $subscription->manufacturer_id,
                // 'from_plan_id' => $subscription->plan_id,
                'paid_amount' => $validated['paid_amount'],
                'to_plan_id' => $subscription->plan_id,
                'event_type' => SubscriptionEventType::SUBSCRIPTION_CREATED->value,
            ]);


            //  Send Notificaiton Subscriber 

            // TODO: Send Notification to Subscriber

            
            // End Notification Subscriber
        }

        return sendResponse(
            status: true,
            message: __('subscription.subscription_created'),
            data: new SubscriptionResource($subscription),
            statusCode: HttpStatus::HTTP_CREATED
        );
    }


    // Show Individual Subscription

    public function show(Request $request)
    {
        $manufacturer = $request->user();
        $subscription = $this->subscriptionService->getSubscriptionByManufacturerId($manufacturer->id);
 
        if (!$subscription) {
            return sendResponse(
                status: false,
                message: __('common.not_found'),
                data: null,
                statusCode: HttpStatus::HTTP_NOT_FOUND
            );
        }
        $subscription->load('manufacturer', 'plan');
        return sendResponse(
            status: true,
            message: __('common.success'),
            data: new SubscriptionResource($subscription),
            statusCode: HttpStatus::HTTP_OK
        );
    }


    public function cancel(Request $request)
    {

         $manufacturer = $request->user();

        $manufacturer->load('subscription');

        if (!$manufacturer->subscription) {
            return sendResponse(
                status: false,
                message: __('common.not_found'),
                data: null,
                statusCode: HttpStatus::HTTP_NOT_FOUND
            );
        }
      
        $this->subscriptionService->cancelSubscriptionPlan($manufacturer->subscription->id);
        return sendResponse(
            status: true,
            message: __('common.cancel_subscription'),
            data: new SubscriptionResource($manufacturer->subscription),
            statusCode: HttpStatus::HTTP_OK
        );
    }


    public function upgrade(SubcriptionUpdgradeRquest $request)
    {
        
        $manufacturer = $request->user();

        $manufacturer->load('subscription');

        if (!$manufacturer->subscription) {
            return sendResponse(
                status: false,
                message: __('subscription.no_subscripiton_found'),
                data: null,
                statusCode: HttpStatus::HTTP_NOT_FOUND
            );
        }

        $validated = $request->validated();
        

        if($manufacturer->subscription->plan_id === $validated['plan_id']) {
            return sendResponse(
                status: false,
                message: __('subscription.same_plan'),
                data: null,
                statusCode: HttpStatus::HTTP_BAD_REQUEST
            );
        }


        $this->paymentCheckService->checkPayment( $validated['payment_method'], $validated );

      
       

       
        try {
            
            Payment::create([
                'payment_id' => $validated['payment_id'],
                'payment_method' => $validated['payment_method'],
                'amount' => $validated['paid_amount'],
                'status' => 'paid',
                'source_id' => $validated['plan_id'],
                'user_id' => $validated['manufacturer_id'],
                'source_type' => Plan::class,
            ]);
        } catch (\Exception $e) {
            return sendResponse(
                status: false,
                message: $e->getMessage(),
                data: null,
                statusCode: HttpStatus::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        if (isset($validated['starts_at'])) {
            $validated['starts_at'] = Carbon::parse($validated['starts_at']);
        }
        if (isset($validated['ends_at'])) {
            $validated['ends_at'] = Carbon::parse($validated['ends_at']);
        }
        if (isset($validated['trial_ends_at'])) {
            $validated['trial_ends_at'] = Carbon::parse($validated['trial_ends_at']);
        }
         $oldSubscription = $manufacturer->subscription;
        $subscription = $this->subscriptionService->updateSubscription($oldSubscription->id, $validated);

        $subscription->load('manufacturer', 'plan');

        if ($subscription) {
            $this->subscriptionLogService->createSubscriptionLog([
                'manufacturer_id' => $subscription->manufacturer_id,
                'from_plan_id' => $oldSubscription->plan_id,
                'paid_amount' => $validated['paid_amount'],
                'to_plan_id' => $subscription->plan_id,
                'event_type' => SubscriptionEventType::SUBSCRIPTION_CREATED->value,
            ]);


            //  Send Notificaiton Subscriber 

            // TODO: Send Notification to Subscriber

            
            // End Notification Subscriber
        }

        return sendResponse(
            status: true,
            message: __('subscription.subscription_updated'),
            data: new SubscriptionResource($subscription),
            statusCode: HttpStatus::HTTP_OK
        );
    }
   
}

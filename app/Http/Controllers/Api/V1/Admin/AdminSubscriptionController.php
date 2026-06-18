<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Admin\AdminSubscriptionResource;
use App\Http\Resources\Api\V1\Admin\PaymentResource;
use App\Http\Resources\Api\V1\Admin\SubscriptionLogResource;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\SubscriptionLog;
use App\Services\Subscription\SubscriptionStatsService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as HttpStatus;

class AdminSubscriptionController extends Controller
{
    public function __construct(
        private readonly SubscriptionStatsService $statsService,
    ) {}

    public function index(Request $request)
    {
        $query = Subscription::query()
            ->with(['manufacturer', 'plan'])
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('plan_id')) {
            $query->where('plan_id', $request->integer('plan_id'));
        }

        if ($request->filled('billing_interval')) {
            $query->where('billing_interval', $request->string('billing_interval'));
        }

        if ($request->has('auto_renew')) {
            $query->where('auto_renew', $request->boolean('auto_renew'));
        }

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->whereHas('manufacturer', function ($manufacturerQuery) use ($search): void {
                $manufacturerQuery
                    ->where('email', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%");
            });
        }

        $subscriptions = $query->paginate(
            perPage: $request->integer('per_page', 15),
            pageName: 'page',
            page: $request->integer('page', 1),
        );

        return sendResponse(
            status: true,
            message: __('common.success'),
            data: AdminSubscriptionResource::collection($subscriptions),
            statusCode: HttpStatus::HTTP_OK,
            additional: [
                'meta' => [
                    'current_page' => $subscriptions->currentPage(),
                    'last_page' => $subscriptions->lastPage(),
                    'per_page' => $subscriptions->perPage(),
                    'total' => $subscriptions->total(),
                ],
            ],
        );
    }

    public function show(int $subscription)
    {
        $record = Subscription::query()
            ->with([
                'manufacturer',
                'plan',
                'payments' => fn ($query) => $query->latest(),
                'logs' => fn ($query) => $query->latest()->with(['fromPlan', 'toPlan']),
            ])
            ->find($subscription);

        if ($record === null) {
            return sendResponse(
                status: false,
                message: __('common.not_found'),
                data: null,
                statusCode: HttpStatus::HTTP_NOT_FOUND,
            );
        }

        return sendResponse(
            status: true,
            message: __('common.success'),
            data: new AdminSubscriptionResource($record),
            statusCode: HttpStatus::HTTP_OK,
        );
    }

    public function payments(Request $request)
    {
        $query = Payment::query()
            ->with(['user', 'source'])
            ->latest();

        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->string('payment_method'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('manufacturer_id')) {
            $query->where('user_id', $request->integer('manufacturer_id'));
        }

        if ($request->filled('plan_id')) {
            $query->where('source_type', \App\Models\Plan::class)
                ->where('source_id', $request->integer('plan_id'));
        }

        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->string('from_date'));
        }

        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->string('to_date'));
        }

        $payments = $query->paginate(
            perPage: $request->integer('per_page', 15),
            pageName: 'page',
            page: $request->integer('page', 1),
        );

        return sendResponse(
            status: true,
            message: __('common.success'),
            data: PaymentResource::collection($payments),
            statusCode: HttpStatus::HTTP_OK,
            additional: [
                'meta' => [
                    'current_page' => $payments->currentPage(),
                    'last_page' => $payments->lastPage(),
                    'per_page' => $payments->perPage(),
                    'total' => $payments->total(),
                ],
            ],
        );
    }

    public function logs(Request $request)
    {
        $query = SubscriptionLog::query()
            ->with(['manufacturer', 'fromPlan', 'toPlan'])
            ->latest();

        if ($request->filled('manufacturer_id')) {
            $query->where('manufacturer_id', $request->integer('manufacturer_id'));
        }

        if ($request->filled('event_type')) {
            $query->where('event_type', $request->string('event_type'));
        }

        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->string('from_date'));
        }

        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->string('to_date'));
        }

        $logs = $query->paginate(
            perPage: $request->integer('per_page', 15),
            pageName: 'page',
            page: $request->integer('page', 1),
        );

        return sendResponse(
            status: true,
            message: __('common.success'),
            data: SubscriptionLogResource::collection($logs),
            statusCode: HttpStatus::HTTP_OK,
            additional: [
                'meta' => [
                    'current_page' => $logs->currentPage(),
                    'last_page' => $logs->lastPage(),
                    'per_page' => $logs->perPage(),
                    'total' => $logs->total(),
                ],
            ],
        );
    }

    public function stats(Request $request)
    {
        $month = $request->filled('month') ? $request->string('month') : null;

        return sendResponse(
            status: true,
            message: __('common.success'),
            data: $this->statsService->stats($month),
            statusCode: HttpStatus::HTTP_OK,
        );
    }
}

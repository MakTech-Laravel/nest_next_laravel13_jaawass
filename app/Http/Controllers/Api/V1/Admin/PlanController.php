<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Filters\Api\V1\Admin\PlanFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\IndexPlansRequest;
use App\Http\Requests\Api\V1\Admin\StorePlanRequest;
use App\Http\Resources\Api\V1\Admin\PlanCollection;
use App\Http\Resources\Api\V1\Admin\PlanResource;
use App\Models\Plan;
use App\Models\PlanFeature;
use App\Services\Currency\PersistedListingCurrencyResolver;
use Symfony\Component\HttpFoundation\Response as HttpStatus;

class PlanController extends Controller
{
    public function __construct(
        private readonly PersistedListingCurrencyResolver $persistedListingCurrency,
    ) {}

    //
    public function index(IndexPlansRequest $request)
    {

        $plans = PlanFilter::apply(Plan::query()->with(['currency','planFeatures']), $request)->paginate(
            perPage: $request->perPage(),
            pageName: 'page',
            page: $request->pageNumber(),
        );

        return sendResponse(
            status: true,
            message: __('common.success'),
            data: PlanResource::collection($plans),
            statusCode: HttpStatus::HTTP_OK
        );
    }

    public function store(StorePlanRequest $request)
    {
        $validated = $request->validated();

        $feature = $request->features;

        unset($validated['features']);

        $bodyCurrencyCode = $validated['currency_code'] ?? null;
        unset($validated['currency_code']);
        $validated['currency_id'] = $this->persistedListingCurrency->resolve($bodyCurrencyCode);

        if (isset($validated['is_popular']) && $validated['is_popular'] === 'true') {
            Plan::where('is_popular', true)->update(['is_popular' => false]);
        }
        $plan = Plan::create($validated);

        $plan->autoTranslate(
            sourceData: [
                'name' => $request->name,
                'description' => $request->description,
                'button_text' => $request->button_text,
            ],
            sourceLocale: $request->locale ?? null,
        );

        foreach ($request->features as $feature) {
            PlanFeature::create([
                'plan_id' => $plan->id,
                'feature_id' => $feature['id'],
                'input_type' => $feature['input_type'],
                'value' => $feature['value'],
                'label' => filled($feature['label'] ?? null) ? $feature['label'] : null,
            ]);
        }

        $plan->load(['planFeatures.feature', 'currency']);

        return sendResponse(
            status: true,
            message: __('common.created'),
            data: new PlanResource($plan),
            statusCode: HttpStatus::HTTP_OK
        );
    }

    public function show($planId)
    {

        $plan = Plan::find($planId);

        if (! $plan) {
            return sendResponse(
                status: false,
                message: __('common.not_found'),
                data: null,
                statusCode: HttpStatus::HTTP_NOT_FOUND
            );
        }

        $plan->load(['planFeatures.feature', 'currency']);

        return sendResponse(
            status: true,
            message: __('common.success'),
            data: new PlanResource($plan),
            statusCode: HttpStatus::HTTP_OK
        );
    }

    public function update($planId, StorePlanRequest $request)
    {

        $plan = Plan::find($planId);

        if (! $plan) {
            return sendResponse(
                status: false,
                message: __('common.not_found'),
                data: null,
                statusCode: HttpStatus::HTTP_NOT_FOUND
            );
        }

        $validated = $request->validated();

        $feature = $request->features;

        unset($validated['features']);

        if (array_key_exists('currency_code', $validated)) {
            $bodyCurrencyCode = $validated['currency_code'];
            unset($validated['currency_code']);
            $validated['currency_id'] = $this->persistedListingCurrency->resolve($bodyCurrencyCode);
        }

        if (isset($validated['is_popular']) && $validated['is_popular'] === 'true') {
            Plan::where('is_popular', true)->update(['is_popular' => false]);
        }
        $plan->update($validated);

        $translatableChanged = array_intersect_key(
            $request->validated(),
            array_flip($plan->translatableFields())
        );

        if (! empty($translatableChanged)) {
            $plan->autoTranslate(
                sourceData: $translatableChanged,
                sourceLocale: $request->locale ?? null,
            );
        }

        PlanFeature::where('plan_id', $planId)->delete();
        foreach ($request->features as $feature) {
            PlanFeature::create([
                'plan_id' => $plan->id,
                'feature_id' => $feature['id'],
                'input_type' => $feature['input_type'],
                'value' => $feature['value'],
                'label' => filled($feature['label'] ?? null) ? $feature['label'] : null,
            ]);
        }

        $plan->load(['planFeatures.feature', 'currency']);

        return sendResponse(
            status: true,
            message: __('common.updated'),
            data: new PlanResource($plan),
            statusCode: HttpStatus::HTTP_OK
        );
    }

    public function destroy($planId)
    {
        $plan = Plan::find($planId);

        if (! $plan) {
            return sendResponse(
                status: false,
                message: __('common.not_found'),
                data: null,
                statusCode: HttpStatus::HTTP_NOT_FOUND
            );
        }

        $plan->delete();

        return sendResponse(
            status: true,
            message: __('common.deleted'),
            data: null,
            statusCode: HttpStatus::HTTP_OK
        );
    }

    public function togglePopular($planId)
    {
        $plan = Plan::find($planId);

        if (! $plan) {
            return sendResponse(
                status: false,
                message: __('common.not_found'),
                data: null,
                statusCode: HttpStatus::HTTP_NOT_FOUND
            );
        }

        Plan::where('is_popular', true)->update(['is_popular' => false]);

        $plan->is_popular = ! $plan->is_popular;
        $plan->save();

        $plan->load('currency');

        return sendResponse(
            status: true,
            message: __('common.popular_status_toggled'),
            data: new PlanResource($plan),
            statusCode: HttpStatus::HTTP_OK
        );
    }

    public function toggleStatus($planId)
    {
        $plan = Plan::find($planId);

        if (! $plan) {
            return sendResponse(
                status: false,
                message: __('common.not_found'),
                data: null,
                statusCode: HttpStatus::HTTP_NOT_FOUND
            );
        }

        $plan->status = ! $plan->status;
        $plan->save();

        $plan->load('currency');

        return sendResponse(
            status: true,
            message: __('common.status_toggled'),
            data: new PlanResource($plan),
            statusCode: HttpStatus::HTTP_OK
        );
    }
}

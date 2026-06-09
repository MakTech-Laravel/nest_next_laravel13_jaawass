<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Filters\Api\V1\Admin\HelpCenterCategoryFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\HelpCenterCategoryPositionRequest;
use App\Http\Requests\Api\V1\Admin\IndexHelpCenterCategoryRequest;
use App\Http\Requests\Api\V1\Admin\StoreHelpCenterCategoryRequest;
use App\Http\Requests\Api\V1\Admin\UpdateHelpCenterCategoryRequest;
use App\Http\Resources\Api\V1\Admin\HelpCenterCategoryResource;
use App\Models\HelpCenterCategory;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response as HttpStatus;

class HelpCenterCategoryController extends Controller
{
    public function index(IndexHelpCenterCategoryRequest $request)
    {
        $helpCenterCategories = HelpCenterCategoryFilter::apply(
            HelpCenterCategory::query()->with('translations'),
            $request,
        )->paginate(
            perPage: $request->perPage(),
            pageName: 'page',
            page: $request->pageNumber(),
        );

        return sendResponse(
            status: true,
            message: __('common.success'),
            data: HelpCenterCategoryResource::collection($helpCenterCategories),
            statusCode: HttpStatus::HTTP_OK,
        );
    }

    public function show(HelpCenterCategory $helpCenterCategory)
    {
        $helpCenterCategory->load('translations');

        return sendResponse(
            status: true,
            message: __('common.success'),
            data: new HelpCenterCategoryResource($helpCenterCategory),
            statusCode: HttpStatus::HTTP_OK,
        );
    }

    public function store(StoreHelpCenterCategoryRequest $request)
    {
        $validated = $request->validated();
        $locale = $validated['locale'] ?? app()->getLocale();
        unset($validated['locale']);

        $category = DB::transaction(function () use ($validated) {
            $sortOrder = $validated['sort_order'] ?? ((int) HelpCenterCategory::query()->max('sort_order') + 1);
            unset($validated['sort_order']);

            HelpCenterCategory::query()
                ->where('sort_order', '>=', $sortOrder)
                ->increment('sort_order');

            return HelpCenterCategory::query()->create([
                ...$validated,
                'sort_order' => $sortOrder,
                'status' => $validated['status'] ?? true,
            ]);
        });

        $category->upsertTranslations([
            $locale => Arr::only($category->only($category->translatableFields()), $category->translatableFields()),
        ]);

        $category->autoTranslate(
            sourceData: Arr::only($request->validated(), $category->translatableFields()),
            sourceLocale: $locale,
        );

        $category->load('translations');

        return sendResponse(
            status: true,
            message: __('common.created'),
            data: new HelpCenterCategoryResource($category),
            statusCode: HttpStatus::HTTP_CREATED,
        );
    }

    public function update(UpdateHelpCenterCategoryRequest $request, HelpCenterCategory $helpCenterCategory)
    {
        $validated = $request->validated();
        $locale = $validated['locale'] ?? $request->query('locale') ?? app()->getLocale();
        unset($validated['locale']);

        $helpCenterCategory->update($validated);

        $translatableChanged = array_intersect_key(
            $validated,
            array_flip($helpCenterCategory->translatableFields()),
        );

        if (! empty($translatableChanged)) {
            $helpCenterCategory->upsertTranslations([
                $locale => $translatableChanged,
            ]);

            $helpCenterCategory->autoTranslate(
                sourceData: $translatableChanged,
                sourceLocale: $locale,
            );
        }

        $helpCenterCategory->load('translations');

        return sendResponse(
            status: true,
            message: __('common.updated'),
            data: new HelpCenterCategoryResource($helpCenterCategory->fresh()),
            statusCode: HttpStatus::HTTP_OK,
        );
    }

    public function destroy(HelpCenterCategory $helpCenterCategory)
    {
        DB::transaction(function () use ($helpCenterCategory) {
            $position = $helpCenterCategory->sort_order;

            $helpCenterCategory->delete();

            HelpCenterCategory::query()
                ->where('sort_order', '>', $position)
                ->decrement('sort_order');
        });

        return sendResponse(
            status: true,
            message: __('common.deleted'),
            data: null,
            statusCode: HttpStatus::HTTP_OK,
        );
    }

    public function updatePosition(
        HelpCenterCategoryPositionRequest $request,
        HelpCenterCategory $helpCenterCategory,
    ) {
        $currentPosition = (int) $helpCenterCategory->sort_order;
        $newPosition = $request->integer('new_position');

        if ($currentPosition === $newPosition) {
            $helpCenterCategory->load('translations');

            return sendResponse(
                status: true,
                message: __('common.position_updated'),
                data: new HelpCenterCategoryResource($helpCenterCategory),
                statusCode: HttpStatus::HTTP_OK,
            );
        }

        DB::transaction(function () use ($helpCenterCategory, $currentPosition, $newPosition) {
            if ($currentPosition > $newPosition) {
                HelpCenterCategory::query()
                    ->where('sort_order', '>=', $newPosition)
                    ->where('sort_order', '<', $currentPosition)
                    ->increment('sort_order');
            } elseif ($currentPosition < $newPosition) {
                HelpCenterCategory::query()
                    ->where('sort_order', '>', $currentPosition)
                    ->where('sort_order', '<=', $newPosition)
                    ->decrement('sort_order');
            }

            $helpCenterCategory->update([
                'sort_order' => $newPosition,
            ]);
        });

        $helpCenterCategory->load('translations');

        return sendResponse(
            status: true,
            message: __('common.position_updated'),
            data: new HelpCenterCategoryResource($helpCenterCategory->fresh()),
            statusCode: HttpStatus::HTTP_OK,
        );
    }
}

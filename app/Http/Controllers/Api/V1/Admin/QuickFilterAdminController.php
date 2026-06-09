<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\QuickFilterType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\QuickFilter\IndexQuickFilterOptionRequest;
use App\Http\Requests\Api\V1\Admin\QuickFilter\SortQuickFilterOptionRequest;
use App\Http\Requests\Api\V1\Admin\QuickFilter\StoreQuickFilterOptionRequest;
use App\Http\Requests\Api\V1\Admin\QuickFilter\ToggleQuickFilterOptionRequest;
use App\Http\Requests\Api\V1\Admin\QuickFilter\UpdateQuickFilterOptionRequest;
use App\Http\Resources\Api\V1\Admin\QuickFilterOptionResource;
use App\Models\QuickFilterOption;
use App\Services\QuickFilterService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response as HttpStatus;

class QuickFilterAdminController extends Controller
{
    public function __construct(
        private readonly QuickFilterService $quickFilters,
    ) {}

    public function counts(): JsonResponse
    {
        return sendResponse(
            true,
            __('common.success'),
            $this->quickFilters->getCounts(),
            HttpStatus::HTTP_OK,
        );
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public function types(): JsonResponse
    {
        $types = array_map(
            fn (QuickFilterType $t) => ['value' => $t->value, 'label' => $t->label()],
            QuickFilterType::cases()
        );

        return sendResponse(
            true,
            __('common.success'),
            $types,
            HttpStatus::HTTP_OK,
        );
    }

    public function index(IndexQuickFilterOptionRequest $request): JsonResponse
    {
        $options = $this->quickFilters->listByType($request->filterType());

        return sendResponse(
            true,
            __('common.success'),
            QuickFilterOptionResource::collection($options),
            HttpStatus::HTTP_OK,
        );
    }

    public function show(QuickFilterOption $quickFilterOption): JsonResponse
    {
        return sendResponse(
            true,
            __('common.success'),
            new QuickFilterOptionResource($quickFilterOption),
            HttpStatus::HTTP_OK,
        );
    }

    public function store(StoreQuickFilterOptionRequest $request): JsonResponse
    {
        $option = $this->quickFilters->create(
            $request->filterType(),
            $request->validated('display_label'),
            $request->validated('value'),
            $request->boolean('is_enabled', true),
        );

        return sendResponse(
            true,
            __('common.created'),
            new QuickFilterOptionResource($option),
            HttpStatus::HTTP_CREATED,
        );
    }

    public function update(UpdateQuickFilterOptionRequest $request, QuickFilterOption $quickFilterOption): JsonResponse
    {
        $option = $this->quickFilters->update($quickFilterOption, $request->validated());

        return sendResponse(
            true,
            __('common.updated'),
            new QuickFilterOptionResource($option),
            HttpStatus::HTTP_OK,
        );
    }

    public function destroy(QuickFilterOption $quickFilterOption): JsonResponse
    {
        $this->quickFilters->delete($quickFilterOption);

        return sendResponse(
            true,
            __('common.deleted'),
            null,
            HttpStatus::HTTP_OK,
        );
    }

    public function toggle(ToggleQuickFilterOptionRequest $request, QuickFilterOption $quickFilterOption): JsonResponse
    {
        $validated = $request->validated();
        $enabled = array_key_exists('is_enabled', $validated) ? (bool) $validated['is_enabled'] : null;
        $option = $this->quickFilters->toggle($quickFilterOption, $enabled);

        return sendResponse(
            true,
            __('common.updated'),
            new QuickFilterOptionResource($option),
            HttpStatus::HTTP_OK,
        );
    }

    public function sort(SortQuickFilterOptionRequest $request, QuickFilterOption $quickFilterOption): JsonResponse
    {
        $this->quickFilters->moveSort($quickFilterOption, $request->validated('direction'));
        $quickFilterOption->refresh();

        return sendResponse(
            true,
            __('common.updated'),
            new QuickFilterOptionResource($quickFilterOption),
            HttpStatus::HTTP_OK,
        );
    }
}

<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Filters\Api\V1\Admin\FaqCategoryFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\FaqCategoryStoreRequest;
use App\Http\Requests\Api\V1\Admin\FaqCategoryUpdateRequest;
use App\Http\Requests\Api\V1\Admin\IndexFaqCategoryReqeust;
use App\Http\Resources\Api\V1\FaqCategoryResource;
use App\Models\FaqCategory;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as HttpStatus;
use Illuminate\Support\Arr;
class FaqCategoryController extends Controller
{
    public function index(IndexFaqCategoryReqeust $request)
    {
        $faqCategories = FaqCategoryFilter::apply(FaqCategory::query()->with(['faqs' => function ($query) {
            $query->orderByRaw('CASE WHEN sort = 0 THEN 1 ELSE 0 END, sort ASC');
        }, 'translations']), $request)->paginate(
            perPage: $request->perPage(),
            pageName: 'page',
            page: $request->pageNumber(),
        );

        return sendResponse(
            status: true,
            message: __('common.success'),
            data: FaqCategoryResource::collection($faqCategories),
            statusCode: HttpStatus::HTTP_OK
        );
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {

        $faqCategory = FaqCategory::find($id);

        if (!$faqCategory) {
            return sendResponse(
                status: false,
                message: __('common.not_found'),
                data: null,
                statusCode: HttpStatus::HTTP_NOT_FOUND
            );
        }

        $faqCategory->load(['faqs' => function ($query) {
            $query->orderByRaw('CASE WHEN sort = 0 THEN 1 ELSE 0 END, sort ASC');
        }]);

        return sendResponse(
            status: true,
            message: __('common.success'),
            data: new FaqCategoryResource($faqCategory),
            statusCode: HttpStatus::HTTP_OK
        );
    }

    // Store Category
    public function store(FaqCategoryStoreRequest $request)
    {

        $data = [
            'name' => $request->name,
            'slug' => $request->slug,
        ];

        $faqCategory = FaqCategory::create($data);

        $faqCategory->autoTranslate(
            sourceData: [
                'name' => $request->name,
            ],
            sourceLocale: $request->locale ?? null,
        );

        return sendResponse(
            status: true,
            message: __('common.created'),
            data: new FaqCategoryResource($faqCategory),
            statusCode: HttpStatus::HTTP_CREATED
        );
    }


    public function update($id, FaqCategoryUpdateRequest $request)
    {


        $category = FaqCategory::find($id);
        if (!$category) {
            return sendResponse(
                status: false,
                message: __('common.not_found'),
                data: null,
                statusCode: HttpStatus::HTTP_NOT_FOUND
            );
        }

        $category->update($request->validated());


         $translatableChanged = Arr::only($request->validated(), $category->translatableFields());

        if (! empty($translatableChanged)) {
            $sourceLocale = $request->locale ?? app()->getLocale();

            // Ensure source-locale row is persisted even when queue workers are not running.
            $category->upsertTranslations([
                $sourceLocale => $translatableChanged,
            ]);

            $category->autoTranslate(
                sourceData: $translatableChanged,
                sourceLocale: $sourceLocale,
            );
        }




        $category->load(['faqs' => function ($query) {
            $query->orderByRaw('CASE WHEN sort = 0 THEN 1 ELSE 0 END, sort ASC');
        }]);

        return sendResponse(
            status: true,
            message: __('common.success'),
            data: new FaqCategoryResource($category),
            statusCode: HttpStatus::HTTP_OK
        );
    }

    public function destroy($id)
    {


        $faqCategory = FaqCategory::find($id);

        if (!$faqCategory) {
            return sendResponse(
                status: false,
                message: __('common.not_found'),
                data: null,
                statusCode: HttpStatus::HTTP_NOT_FOUND
            );
        }

        $faqCategory->delete();

        return sendResponse(
            status: true,
            message: __('common.deleted'),
            statusCode: HttpStatus::HTTP_OK
        );
    }



    // updatePosition


    public function categoryPosition(Request $request, $id)
    {

        $faqCategory = FaqCategory::find($id);
        if (!$faqCategory) {
            return sendResponse(
                status: false,
                message: __('common.not_found'),
                data: null,
                statusCode: HttpStatus::HTTP_NOT_FOUND
            );
        }

        $request->validate([
            'current_position' => 'required|integer',
            'new_position' => 'required|integer',
        ], [
            'current_position.required' => 'Current position is required',
            'new_position.required' => 'New position is required',
        ]);

        if ($request->current_position > $request->new_position) {
            // Move up
            FaqCategory::where('sort', '>=', $request->new_position)->where('sort', '<', $request->current_position)->increment('sort');
        } else {
            // Move down
            FaqCategory::where('sort', '>', $request->current_position)->where('sort', '<=', $request->new_position)->decrement('sort');
        }



        $faqCategory->update([
            'sort' => $request->new_position,
        ]);

        return sendResponse(
            status: true,
            message: __('common.position_updated'),
            data: new FaqCategoryResource($faqCategory),
            statusCode: HttpStatus::HTTP_OK
        );
    }
}

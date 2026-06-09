<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Filters\Api\V1\Admin\ArticleCategoryFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\IndexArticleCategoriesRequest;
use App\Http\Requests\Api\V1\Admin\StoreArticleCategoryRequest;
use App\Http\Requests\Api\V1\Admin\UpdateArticleCategoryRequest;
use App\Http\Resources\Api\V1\Admin\ArticleCategoryResource;
use App\Models\ArticleCategory;
use App\Services\ArticleCategoryService;
use Symfony\Component\HttpFoundation\Response as HttpStatus;

class ArticleCategoryController extends Controller
{



    public function __construct(protected ArticleCategoryService $articleCategoryService) {}

    /**
     * Display a listing of the resource.
     */
    public function index(IndexArticleCategoriesRequest $request)
    {
        $articleCategories = ArticleCategoryFilter::apply(ArticleCategory::query(), $request)->paginate(
            perPage: $request->perPage(),
            pageName: 'page',
            page: $request->pageNumber(),
        );

        return sendResponse(
            status: true,
            message: __('common.success'),
            data: ArticleCategoryResource::collection($articleCategories),
            statusCode: HttpStatus::HTTP_OK
        );
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create() {}

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreArticleCategoryRequest $request)
    {
        //
        $validated =  $request->validated();

        try {

            $articleCategory = $this->articleCategoryService->create($validated);


           
            $articleCategory->autoTranslate(
                sourceData: [
                    'name' => $request->name,
                ],
                sourceLocale: $request->locale ?? null,
            );
            return sendResponse(
                true,
                new ArticleCategoryResource($articleCategory),
                __('common.created'),
                HttpStatus::HTTP_CREATED,
            );
        } catch (\Exception $e) {
            return sendResponse(
                false,
                null,
                __('common.error'),
                HttpStatus::HTTP_INTERNAL_SERVER_ERROR,
            );
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $articleCategory = $this->articleCategoryService->find($id);

            return sendResponse(
                status: true,
                message: __('common.success'),
                data: new ArticleCategoryResource($articleCategory),
                statusCode: HttpStatus::HTTP_OK,
            );
        } catch (\Exception $e) {
            return sendResponse(
                status: false,
                message: __('common.error'),
                data: null,
                statusCode: HttpStatus::HTTP_INTERNAL_SERVER_ERROR,
            );
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function update(string $id, UpdateArticleCategoryRequest $request)
    {
        $validated = $request->validated();

        if (!$id) {
            return sendResponse(
                false,
                null,
                __('common.not_found'),
                HttpStatus::HTTP_NOT_FOUND,
            );
        }

        try {
            $articleCategory = $this->articleCategoryService->find($id);


            $articleCategory->update($validated);


            $translatableChanged = array_intersect_key(
                $request->validated(),
                array_flip($articleCategory->translatableFields())
            );

            if (! empty($translatableChanged)) {
                $sourceLocale = $request->input('locale') ?? app()->getLocale();

                // Ensure source-locale row is persisted even when queue workers are not running.
                $articleCategory->upsertTranslations([
                    $sourceLocale => $translatableChanged,
                ]);

                $articleCategory->autoTranslate(
                    sourceData: $translatableChanged,
                    sourceLocale: $sourceLocale,
                );
            }


            return sendResponse(
                status: true,
                data: new ArticleCategoryResource($articleCategory),
                message: __('common.success'),
                statusCode: HttpStatus::HTTP_OK,
            );
        } catch (\Exception $e) {
            return sendResponse(
                false,
                null,
                __('common.error'),
                HttpStatus::HTTP_INTERNAL_SERVER_ERROR,
            );
        }
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $articleCategory = $this->articleCategoryService->find($id);
            $articleCategory->delete();

            return sendResponse(
                true,

                __('common.success'),
                null,
                HttpStatus::HTTP_OK,
            );
        } catch (\Exception $e) {
            return sendResponse(
                false,
                __('common.error'),
                null,

                HttpStatus::HTTP_INTERNAL_SERVER_ERROR,
            );
        }
    }
}

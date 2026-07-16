<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\ArticleStatusEnum;
use App\Filters\Api\V1\Admin\ArticleFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\IndexArticlesRequest;
use App\Http\Requests\Api\V1\Admin\StoreArticleRequest;
use App\Http\Requests\Api\V1\Admin\UpdateArticleRequest;
use App\Http\Resources\Api\V1\Admin\ArticleResource;
use App\Models\Article;
use App\Services\ArticleService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response as HttpStatus;

class ArticleController extends Controller
{
    public function __construct(protected ArticleService $articleService) {}

    /**
     * Store on the public disk the same way product images do:
     * storage/app/public/articles/{file} → /storage/articles/{file}
     */
    private function storeArticleImage(UploadedFile $file): string
    {
        $originalName = str_replace(
            ' ',
            '_',
            pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)
        );

        $fileName = $originalName.'_'.uniqid().'.'.$file->getClientOriginalExtension();
        $path = 'articles/'.$fileName;

        Storage::disk('public')->put($path, file_get_contents($file));

        return $path;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(IndexArticlesRequest $request)
    {
        $articles = ArticleFilter::apply(
            Article::query()->with(['category', 'creator']),
            $request
        )->paginate(
            perPage: $request->perPage(),
            pageName: 'page',
            page: $request->pageNumber(),
        );

        return sendResponse(
            status: true,
            message: __('common.success'),
            data: ArticleResource::collection($articles),
            statusCode: HttpStatus::HTTP_OK
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreArticleRequest $request)
    {
        $validated = $request->validated();

        if (
            isset($validated['article_image']) &&
            $validated['article_image'] instanceof UploadedFile
        ) {
            $validated['article_image'] = $this->storeArticleImage($validated['article_image']);
        } else {
            unset($validated['article_image']);
        }

        $validated['creator_id'] = $request->user()->id;

        $validated['published_at'] = $validated['status'] !==  ArticleStatusEnum::DRAFT->value ? now() : null;
        try {
            $article = $this->articleService->create($validated);

            $article->autoTranslate(
                sourceData: [
                    'title' => $request->title,
                    'content' => $request->content,
                    'excerpt' => $request->excerpt,
                ],
                sourceLocale: $request->locale ?? null,
            );

            $article->refresh();
            $article->load(['category', 'creator']);



            return sendResponse(
                status: true,
                message: __('common.created'),
                data: new ArticleResource($article),
                statusCode: HttpStatus::HTTP_CREATED
            );
        } catch (\Exception $e) {
            return sendResponse(
                status: false,
                message: __('common.error'),
                data: null,
                statusCode: HttpStatus::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $article = $this->articleService->find($id);
            $article->incrementViews();
            $article->load(['category', 'creator']);

            return sendResponse(
                status: true,
                message: __('common.success'),
                data: new ArticleResource($article),
                statusCode: HttpStatus::HTTP_OK
            );
        } catch (\Exception $e) {
            return sendResponse(
                status: false,
                message: __('common.not_found'),
                data: null,
                statusCode: HttpStatus::HTTP_NOT_FOUND
            );
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(string $id, UpdateArticleRequest $request)
    {
        $validated = $request->validated();

        if (
            isset($validated['article_image']) &&
            $validated['article_image'] instanceof UploadedFile
        ) {
            $validated['article_image'] = $this->storeArticleImage($validated['article_image']);
        } else {
            unset($validated['article_image']);
        }

        try {
            $article = $this->articleService->find($id);
            $previousImage = $article->article_image;
            $article = $this->articleService->update($article, $validated);

            if (
                isset($validated['article_image'])
                && $previousImage
                && $previousImage !== $validated['article_image']
                && Storage::disk('public')->exists($previousImage)
            ) {
                Storage::disk('public')->delete($previousImage);
            }
            $translatableChanged = array_intersect_key(
                $request->validated(),
                array_flip($article->translatableFields())
            );

            if (! empty($translatableChanged)) {
                $sourceLocale = $request->input('locale') ?? app()->getLocale();

                $article->upsertTranslations([
                    $sourceLocale => $translatableChanged,
                ]);

                $article->autoTranslate(
                    sourceData: $translatableChanged,
                    sourceLocale: $sourceLocale,
                );
            }

            $article->load(['category', 'creator']);

            return sendResponse(
                status: true,
                message: __('common.updated'),
                data: new ArticleResource($article),
                statusCode: HttpStatus::HTTP_OK
            );
        } catch (\Exception $e) {
            return sendResponse(
                status: false,
                message: __('common.error'),
                data: null,
                statusCode: HttpStatus::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $article = $this->articleService->find($id);
            $this->articleService->delete($article);

            return sendResponse(
                status: true,
                message: __('common.deleted'),
                data: null,
                statusCode: HttpStatus::HTTP_OK
            );
        } catch (\Exception $e) {
            return sendResponse(
                status: false,
                message: __('common.error'),
                data: null,
                statusCode: HttpStatus::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function toggleStatus(string $id)
    {
        try {
            $article = $this->articleService->find($id);

            // If currently published -> move to draft
            if ($article->status === ArticleStatusEnum::PUBLISHED->value) {

                $article->update([
                    'status' => ArticleStatusEnum::DRAFT->value,
                ]);
            } else {

                // Validate required fields before publishing
                if (
                    empty($article->content) ||
                    empty($article->author) ||
                    empty($article->title) ||
                    empty($article->article_category_id)
                ) {
                    return sendResponse(
                        status: false,
                        message: __('common.error'),
                        data: [
                            'message' => 'Required information is missing for publishing the article.'
                        ],
                        statusCode: HttpStatus::HTTP_BAD_REQUEST
                    );
                }

                $article->update([
                    'published_at' => $article->published_at ?? now(),
                    'status' => ArticleStatusEnum::PUBLISHED->value,
                ]);
            }

            return sendResponse(
                status: true,
                message: __('common.updated'),
                data: new ArticleResource($article->fresh()),
                statusCode: HttpStatus::HTTP_OK
            );
        } catch (\Exception $e) {

            return sendResponse(
                status: false,
                message: __('common.error'),
                data: null,
                statusCode: HttpStatus::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function stats()
    {
        try {
            $stats = [
                'total' => Article::count(),
                'published' => Article::where('status', ArticleStatusEnum::PUBLISHED->value)->count(),
                'draft' => Article::where('status', ArticleStatusEnum::DRAFT->value)->count(),
                'featured' => Article::where('is_featured', true)->count(),
                'total_views' => Article::sum('views'),
            ];

            return sendResponse(
                status: true,
                message: __('common.success'),
                data: $stats,
                statusCode: HttpStatus::HTTP_OK
            );
        } catch (\Exception $e) {
            return sendResponse(
                status: false,
                message: __('common.error'),
                data: null,
                statusCode: HttpStatus::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}

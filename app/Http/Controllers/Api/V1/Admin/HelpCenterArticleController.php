<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Filters\Api\V1\Admin\HelpCenterArticleFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\HelpCenterArticlePositionRequest;
use App\Http\Requests\Api\V1\Admin\IndexHelpCenterArticleRequest;
use App\Http\Requests\Api\V1\Admin\StoreHelpCenterArticleRequest;
use App\Http\Requests\Api\V1\Admin\UpdateHelpCenterArticleRequest;
use App\Http\Resources\Api\V1\Admin\HelpCenterArticleResource;
use App\Models\HelpCenterArticle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;
use Symfony\Component\HttpFoundation\Response as HttpStatus;

class HelpCenterArticleController extends Controller
{
    public function index(IndexHelpCenterArticleRequest $request)
    {
        $articles = HelpCenterArticleFilter::apply(
            HelpCenterArticle::query()->with(['category', 'translations', 'steps.translations']),
            $request,
        )->paginate(
            perPage: $request->perPage(),
            pageName: 'page',
            page: $request->pageNumber(),
        );

        return sendResponse(
            status: true,
            message: __('common.success'),
            data: HelpCenterArticleResource::collection($articles),
            statusCode: HttpStatus::HTTP_OK,
        );
    }

    public function show($id)
    {
        $article = HelpCenterArticle::query()->find($id);

        if (!$article) {
            return $this->notFoundResponse();
        }

        return sendResponse(
            status: true,
            message: __('common.success'),
            data: new HelpCenterArticleResource($article),
            statusCode: HttpStatus::HTTP_OK,
        );
    }

    public function showArticleInFrontend($id)
    {
        $article = HelpCenterArticle::query()->find($id);

        if (!$article) {
            return $this->notFoundResponse();
        }

        $article->load(['category', 'steps']);
        $article->increment('views');

        return sendResponse(
            status: true,
            message: __('common.success'),
            data: new HelpCenterArticleResource($article),
            statusCode: HttpStatus::HTTP_OK,
        );
    }

    public function store(StoreHelpCenterArticleRequest $request)
    {
        $validated = $request->validated();
        $locale = $validated['locale'] ?? app()->getLocale();
        $steps = $validated['steps'] ?? [];
        unset($validated['locale'], $validated['steps']);

        $article = DB::transaction(function () use ($validated) {
            $categoryId = (int) $validated['help_center_category_id'];
            $sortOrder = $validated['sort_order'] ?? ((int) HelpCenterArticle::query()
                ->where('help_center_category_id', $categoryId)
                ->max('sort_order') + 1);
            unset($validated['sort_order']);

            HelpCenterArticle::query()
                ->where('help_center_category_id', $categoryId)
                ->where('sort_order', '>=', $sortOrder)
                ->increment('sort_order');

            return HelpCenterArticle::query()->create([
                ...$validated,
                'sort_order' => $sortOrder,
                'status' => $validated['status'] ?? true,
                'help_full' => $validated['help_full'] ?? 0,
                'not_help_full' => $validated['not_help_full'] ?? 0,
            ]);
        });

        $article->upsertTranslations([
            $locale => Arr::only($article->only($article->translatableFields()), $article->translatableFields()),
        ]);

        $article->autoTranslate(
            sourceData: Arr::only($request->validated(), $article->translatableFields()),
            sourceLocale: $locale,
        );

        if ($steps !== []) {
            $article->syncSteps($steps, $locale);
        }

        $article->load(['translations', 'steps.translations']);

        return sendResponse(
            status: true,
            message: __('common.created'),
            data: new HelpCenterArticleResource($article),
            statusCode: HttpStatus::HTTP_CREATED,
        );
    }

    public function update(UpdateHelpCenterArticleRequest $request, $id)
    {
        $article = HelpCenterArticle::query()->find($id);

        if (!$article) {
            return $this->notFoundResponse();
        }

        $validated = $request->validated();
        $locale = $validated['locale'] ?? $request->query('locale') ?? app()->getLocale();
        $steps = $validated['steps'] ?? null;
        unset($validated['locale'], $validated['steps']);

        $article->update($validated);

        $translatableChanged = array_intersect_key(
            $validated,
            array_flip($article->translatableFields()),
        );

        if (!empty($translatableChanged)) {
            $article->upsertTranslations([
                $locale => $translatableChanged,
            ]);

            $article->autoTranslate(
                sourceData: $translatableChanged,
                sourceLocale: $locale,
            );
        }

        if ($request->has('steps') && is_array($steps)) {
            $article->syncSteps($steps, $locale);
        }

        $article->load(['translations', 'steps.translations']);

        return sendResponse(
            status: true,
            message: __('common.updated'),
            data: new HelpCenterArticleResource($article->fresh()),
            statusCode: HttpStatus::HTTP_OK,
        );
    }

    public function destroy($id)
    {
        $article = HelpCenterArticle::query()->find($id);

        if (!$article) {
            return $this->notFoundResponse();
        }

        DB::transaction(function () use ($article) {
            $categoryId = $article->help_center_category_id;
            $position = $article->sort_order;

            $article->delete();

            HelpCenterArticle::query()
                ->where('help_center_category_id', $categoryId)
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

    public function updatePosition(HelpCenterArticlePositionRequest $request, $id)
    {
        $article = HelpCenterArticle::query()->find($id);

        if (!$article) {
            return $this->notFoundResponse();
        }

        $currentPosition = (int) $article->sort_order;
        $newPosition = $request->integer('new_position');
        $categoryId = $article->help_center_category_id;

        if ($currentPosition === $newPosition) {
            return sendResponse(
                status: true,
                message: __('common.position_updated'),
                data: new HelpCenterArticleResource($article),
                statusCode: HttpStatus::HTTP_OK,
            );
        }

        DB::transaction(function () use ($article, $currentPosition, $newPosition, $categoryId) {
            $scoped = HelpCenterArticle::query()->where('help_center_category_id', $categoryId);

            if ($currentPosition > $newPosition) {
                $scoped
                    ->clone()
                    ->where('sort_order', '>=', $newPosition)
                    ->where('sort_order', '<', $currentPosition)
                    ->increment('sort_order');
            } elseif ($currentPosition < $newPosition) {
                $scoped
                    ->clone()
                    ->where('sort_order', '>', $currentPosition)
                    ->where('sort_order', '<=', $newPosition)
                    ->decrement('sort_order');
            }

            $article->update(['sort_order' => $newPosition]);
        });

        return sendResponse(
            status: true,
            message: __('common.position_updated'),
            data: new HelpCenterArticleResource($article->fresh()),
            statusCode: HttpStatus::HTTP_OK,
        );
    }

    public function articleHelpful(Request $request, $id)
    {
        $validated = $request->validate([
            'is_helpful' => 'required|boolean',
        ]);

        $article = HelpCenterArticle::query()->find($id);

        if (!$article) {
            return $this->notFoundResponse();
        }

        if ($validated['is_helpful']) {
            $article->increment('help_full');
        } else {
            $article->increment('not_help_full');
        }

        return sendResponse(
            status: true,
            message: __('common.updated'),
            data: new HelpCenterArticleResource($article->fresh()),
            statusCode: HttpStatus::HTTP_OK,
        );
    }

    private function notFoundResponse(): JsonResponse
    {
        return sendResponse(
            status: false,
            message: __('common.not_found'),
            data: null,
            statusCode: HttpStatus::HTTP_NOT_FOUND,
        );
    }
}

<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Filters\Api\V1\Admin\IndustryFilter;
use App\Filters\Api\V1\Admin\SubCategoryFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\IndexSubCategoryRequest;
use App\Http\Requests\Api\V1\Admin\IndustryIndexRequest;
use App\Http\Requests\Api\V1\Admin\IndustryStoreRequest;
use App\Http\Requests\Api\V1\Admin\IndustryUpdateRequest;
use App\Http\Requests\Api\V1\Admin\SubCategoryStoreRequest;
use App\Http\Requests\Api\V1\Admin\SubCategoryUpdateRequest;
use App\Http\Resources\Api\V1\Admin\SubCategoryResource;
use App\Http\Resources\Api\V1\IndustryResource;
use App\Models\Industry;
use App\Models\SubCategory;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response as HttpStatus;

class AdminCategoryAndSubCategoryController extends Controller
{
    public function index(IndustryIndexRequest $request)
    {
        $industries = IndustryFilter::apply(
            Industry::query()
                ->withSupplierCount()
                ->with([
            'subCategories' => function ($query) {
                $query->orderByRaw('CASE WHEN sort_order = 0 THEN 1 ELSE 0 END, sort_order ASC');
            },
            'subCategories.translations',
            'translations',
        ]),
            $request
        )->paginate(
            perPage: $request->perPage(),
            pageName: 'page',
            page: $request->pageNumber(),
        );

        return sendResponse(
            status: true,
            message: __('common.success'),
            data: IndustryResource::collection($industries),
            statusCode: HttpStatus::HTTP_OK
        );
    }

    public function store(IndustryStoreRequest $request)
    {
        $validated = $request->validated();



        // if ($request->icon instanceof UploadedFile) {
        //     $name = uniqid() . '.' . $request->icon->getClientOriginalExtension();
        //     $iconPath = Storage::disk('public')->putFileAs('industries', $request->icon, $name);
        //     $validated['icon'] = $iconPath;
        // } else {
        //     $validated['icon'] = null;
        // }

        $slug = $validated['slug'] ?? str()->slug($validated['name']);

        $originalSlug = $slug;
        $count = 1;
        while (Industry::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $count;
            $count++;
        }

       
        if ($validated['featured'] ?? 0 && Industry::where('featured', true)->count() >= 8) {
            return sendResponse(
                status: false,
                message: __('common.featured_limit'),
                data: null,
                statusCode: HttpStatus::HTTP_BAD_REQUEST
            );
        }


        $industry = Industry::create([
            'name' => $validated['name'],
            'icon' => $validated['icon'],
            'icon_color' => $validated['icon_color'] ?? '',
            'color' => $validated['color'],
            'title_color' => $validated['title_color'] ?? null,
            'desc_color' => $validated['desc_color'] ?? null,
            'btn_color' => $validated['btn_color'] ?? null,
            'supplier_color' => $validated['supplier_color'] ?? null,
            'description' => $validated['description'],
            'slug' => $slug,
            'featured' => $validated['featured'] ?? false,
        ]);

        $industry->autoTranslate(
            sourceData: [
                'name' => (string) $request->input('name'),
            ],
            sourceLocale: $request->input('locale'),
        );

        return sendResponse(
            status: true,
            message: __('common.success'),
            data: new IndustryResource($industry),
            statusCode: HttpStatus::HTTP_CREATED
        );
    }

    public function show($id)
    {
        $industry = Industry::query()->withSupplierCount()->find($id);

        if (! $industry) {
            return sendResponse(
                status: false,
                message: __('common.not_found'),
                data: null,
                statusCode: HttpStatus::HTTP_NOT_FOUND
            );
        }

        $industry->load(['subCategories.translations', 'translations']);

        return sendResponse(
            status: true,
            message: __('common.success'),
            data: new IndustryResource($industry),
            statusCode: HttpStatus::HTTP_OK
        );
    }

    public function update(IndustryUpdateRequest $request, $category_id)
    {
        $industry = Industry::find($category_id);

        if (! $industry) {
            return sendResponse(
                status: false,
                message: __('common.not_found'),
                data: null,
                statusCode: HttpStatus::HTTP_NOT_FOUND
            );
        }

        $validated = $request->validated();

        if ($validated['featured'] ?? 0 && Industry::where('featured', true)->count() >= 8) {
            return sendResponse(
                status: false,
                message: __('common.featured_limit'),
                data: null,
                statusCode: HttpStatus::HTTP_BAD_REQUEST
            );
        }

        // if ($request->icon instanceof UploadedFile) {
        //     $name = uniqid() . '.' . $request->icon->getClientOriginalExtension();
        //     $iconPath = Storage::disk('public')->putFileAs('industries', $request->icon, $name);
        //     $validated['icon'] = $iconPath;

        //     if ($industry->icon && Storage::disk('public')->exists($industry->icon)) {
        //         Storage::disk('public')->delete($industry->icon);
        //     }
        // } else {
        //     $validated['icon'] = null;
        // }

        $slug = $validated['slug'];
        str()->slug($validated['name']);
        $originalSlug = $slug;
        $count = 1;
        while (Industry::where('slug', $slug)->where('id', '!=', $industry->id)->exists()) {
            $slug = $originalSlug . '-' . $count;
            $count++;
        }
        $industry->update([
            'name' => $validated['name'],
            'icon' => $validated['icon'],
            'color' => $validated['color'],
            'icon_color' => $validated['icon_color'] ?? '',
            'title_color' => $validated['title_color'] ?? null,
            'desc_color' => $validated['desc_color'] ?? null,
            'btn_color' => $validated['btn_color'] ?? null,
            'supplier_color' => $validated['supplier_color'] ?? null,
            'slug' => $slug,
            'description' => $validated['description'] ?? null,
            'featured' => $validated['featured'] ?? false,
        ]);

        $translatableChanged = array_intersect_key(
            $request->validated(),
            array_flip($industry->translatableFields())
        );

        if (! empty($translatableChanged)) {
            $industry->autoTranslate(
                sourceData: $translatableChanged,
                sourceLocale: $request->input('locale'),
            );
        }

        $industry = $industry->refresh();

        return sendResponse(
            status: true,
            message: __('common.success'),
            data: new IndustryResource($industry),
            statusCode: HttpStatus::HTTP_OK
        );
    }

    public function destroy($id)
    {
        $industry = Industry::find($id);

        if (! $industry) {
            return sendResponse(
                status: false,
                message: __('common.not_found'),
                data: null,
                statusCode: HttpStatus::HTTP_NOT_FOUND
            );
        }

        $iconPath = null;
        if (! empty($industry->icon)) {
            $iconPath = $industry->icon;
        }

        $delete = $industry->delete();

        // if ($delete && $iconPath) {
        //     if (Storage::disk('public')->exists($iconPath)) {
        //         Storage::disk('public')->delete($iconPath);
        //     }
        // }

        return sendResponse(
            status: true,
            message: __('common.success'),
            data: new IndustryResource($industry),
            statusCode: HttpStatus::HTTP_OK
        );
    }

    public function toggleFeatured($id)
    {
        $industry = Industry::find($id);
        if (! $industry) {
            return sendResponse(
                status: false,
                message: __('common.not_found'),
                data: null,
                statusCode: HttpStatus::HTTP_NOT_FOUND
            );
        }

        $count = Industry::where('featured', true)->count();
        if ($count >= 8 && ! $industry->featured) {
            return sendResponse(
                status: false,
                message: __('common.featured_limit'),
                data: null,
                statusCode: HttpStatus::HTTP_BAD_REQUEST
            );
        }

        $industry->update([
            'featured' => ! $industry->featured,
        ]);

        return sendResponse(
            status: true,
            message: __('common.success'),
            data: new IndustryResource($industry),
            statusCode: HttpStatus::HTTP_OK
        );
    }

    // Sub Categories
    public function indexSubcategories(IndexSubCategoryRequest $request)
    {

        $subcategories = SubCategoryFilter::apply(
            SubCategory::query()->with(['category', 'translations']),
            $request
        )->paginate(
            perPage: $request->perPage(),
            pageName: 'page',
            page: $request->pageNumber()
        );

        return sendResponse(
            status: true,
            message: __('common.success'),
            data: SubCategoryResource::collection($subcategories),
            statusCode: HttpStatus::HTTP_OK
        );
    }

    public function storeSubcategory(SubCategoryStoreRequest $request)
    {
        $validated = $request->validated();

        $baseSlug = $validated['slug'] ?? str()->slug($validated['name']);
        $slug = $baseSlug;
        $originalSlug = $slug;
        $count = 1;
        while (SubCategory::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $count;
            $count++;
        }

        $subcategory = SubCategory::create([
            'industry_id' => $validated['industry_id'],
            'name' => $validated['name'],
            'slug' => $slug,
            'description' => $validated['description'] ?? null,
            'icon' => $validated['icon'] ?? null,
            'tags' => $validated['tags'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        $subcategory->autoTranslate(
            sourceData: [
                'name' => $validated['name'],
            ],
            sourceLocale: $request->input('locale'),
        );

        $subcategory->load(['category', 'translations']);

        return sendResponse(
            status: true,
            message: __('common.success'),
            data: new SubCategoryResource($subcategory),
            statusCode: HttpStatus::HTTP_CREATED
        );
    }

    public function showSubcategory($id)
    {
        $subcategory = SubCategory::find($id);

        if (! $subcategory) {
            return sendResponse(
                status: false,
                message: __('common.not_found'),
                data: null,
                statusCode: HttpStatus::HTTP_NOT_FOUND
            );
        }

        $subcategory->load(['category', 'translations']);

        return sendResponse(
            status: true,
            message: __('common.success'),
            data: new SubCategoryResource($subcategory),
            statusCode: HttpStatus::HTTP_OK
        );
    }

    public function destroySubcategory($id)
    {
        $subcategory = SubCategory::find($id);

        if (! $subcategory) {
            return sendResponse(
                status: false,
                message: __('common.not_found'),
                data: null,
                statusCode: HttpStatus::HTTP_NOT_FOUND
            );
        }

        $iconPath = $subcategory->icon;
        if (
            is_string($iconPath) && $iconPath !== ''
            && ! str_starts_with(ltrim($iconPath, '/'), 'http')
            && Storage::disk('public')->exists($iconPath)
        ) {
            Storage::disk('public')->delete($iconPath);
        }

        $subcategory->delete();

        return sendResponse(
            status: true,
            message: __('common.success'),
            data: new SubCategoryResource($subcategory),
            statusCode: HttpStatus::HTTP_OK
        );
    }

    public function updateSubcategory(SubCategoryUpdateRequest $request, $id)
    {
        $subcategory = SubCategory::find($id);

        if (! $subcategory) {
            return sendResponse(
                status: false,
                message: __('common.not_found'),
                data: null,
                statusCode: HttpStatus::HTTP_NOT_FOUND
            );
        }

        $validated = $request->validated();

        $baseSlug = $validated['slug'] ?? str()->slug($validated['name']);
        $slug = $baseSlug;
        $originalSlug = $slug;
        $count = 1;
        while (SubCategory::where('slug', $slug)->where('id', '!=', $subcategory->id)->exists()) {
            $slug = $originalSlug . '-' . $count;
            $count++;
        }

        $previousIcon = $subcategory->icon;
        if (
            array_key_exists('icon', $validated)
            && is_string($previousIcon)
            && $previousIcon !== ''
            && ($validated['icon'] ?? null) !== $previousIcon
            && ! str_starts_with(ltrim($previousIcon, '/'), 'http')
            && Storage::disk('public')->exists($previousIcon)
        ) {
            Storage::disk('public')->delete($previousIcon);
        }

        $subcategory->update([
            'industry_id' => $validated['industry_id'],
            'name' => $validated['name'],
            'slug' => $slug,
            'description' => $validated['description'] ?? null,
            'tags' => array_key_exists('tags', $validated) ? $validated['tags'] : $subcategory->tags,
            'sort_order' => $validated['sort_order'] ?? $subcategory->sort_order,
            'icon' => array_key_exists('icon', $validated) ? $validated['icon'] : $subcategory->icon,
        ]);

        $translatableChanged = array_intersect_key(
            $request->validated(),
            array_flip($subcategory->translatableFields())
        );

        if (! empty($translatableChanged)) {
            $subcategory->autoTranslate(
                sourceData: $translatableChanged,
                sourceLocale: $request->input('locale'),
            );
        }

        $subcategory->refresh()->load(['category', 'translations']);

        return sendResponse(
            status: true,
            message: __('common.success'),
            data: new SubCategoryResource($subcategory),
            statusCode: HttpStatus::HTTP_OK
        );
    }

    // Industry position update
    public function industryPosition(Request $request, $id)
    {
        $industry = Industry::find($id);
        if (! $industry) {
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
            Industry::where('sort_order', '>=', $request->new_position)->where('sort_order', '<', $request->current_position)->increment('sort_order');
        } else {
            // Move down
            Industry::where('sort_order', '>', $request->current_position)->where('sort_order', '<=', $request->new_position)->decrement('sort_order');
        }

        $industry->update([
            'sort_order' => $request->new_position,
        ]);

        return sendResponse(
            status: true,
            message: __('common.position_updated'),
            data: new IndustryResource($industry),
            statusCode: HttpStatus::HTTP_OK
        );
    }

    // SubCategory position update
    public function subcategoryPosition(Request $request, $id)
    {
        $subcategory = SubCategory::find($id);
        if (! $subcategory) {
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
            SubCategory::where('sort_order', '>=', $request->new_position)->where('sort_order', '<', $request->current_position)->increment('sort_order');
        } else {
            // Move down
            SubCategory::where('sort_order', '>', $request->current_position)->where('sort_order', '<=', $request->new_position)->decrement('sort_order');
        }

        $subcategory->update([
            'sort_order' => $request->new_position,
        ]);

        return sendResponse(
            status: true,
            message: __('common.position_updated'),
            data: new SubCategoryResource($subcategory),
            statusCode: HttpStatus::HTTP_OK
        );
    }

    public function getAllCategories()
    {
        $categories = Industry::query()
            ->withSupplierCount()
            ->with(['subCategories.translations', 'translations'])
            ->get();

        return sendResponse(
            status: true,
            message: __('common.success'),
            data: IndustryResource::collection($categories),
            statusCode: HttpStatus::HTTP_OK
        );
    }
}

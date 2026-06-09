<?php

namespace App\Http\Controllers\Api\V1\Manufacturer;

use App\Filters\Api\V1\Manufacturer\CatalogFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Manufacturer\ManufacturerCatalogIndexRequest;
use App\Http\Requests\Api\V1\Manufacturer\ManufacturerCatalogStoreRequest;
use App\Http\Resources\Api\V1\Manufacturer\CatalogResource;
use App\Models\Catalog;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response as HttpStatus;

class ManufacturerCatalogController extends Controller
{
    public function index(ManufacturerCatalogIndexRequest $request)
    {
        $catalogs = CatalogFilter::apply(Catalog::query()->with([
            'user',
        ]), $request)->paginate(
            perPage: $request->perPage(),
            pageName: 'page',
            page: $request->pageNumber(),
        );

        return sendResponse(
            true,
            __('common.success'),
            CatalogResource::collection($catalogs),
            HttpStatus::HTTP_OK
        );
    }

    public function store(ManufacturerCatalogStoreRequest $request)
    {
        $validated = $request->validated();

        $file = $request->file('catalog');
        $file_path = $this->storeCatalogFile($file, $validated['name']);

        $catalog = Catalog::create([
            'file_path' => $file_path,
            'name' => $validated['name'],
            'file_size' => $file->getSize(),
            'user_id' => $request->user()->id,
            'status' => $validated['status'],
        ]);

        $catalog->autoTranslate(
            ['name' => $validated['name']],
            sourceLocale: $request->input('locale'),
        );

        return sendResponse(
            true,
            __('common.success'),
            new CatalogResource($catalog),
            HttpStatus::HTTP_OK,
        );
    }

    public function show(Request $request, $catalog_id)
    {
        $catalog = Catalog::where('id', $catalog_id)->where('user_id', $request->user()->id)->first();
        if (! $catalog) {
            return sendResponse(
                false,
                __('common.not_found'),
                null,
                HttpStatus::HTTP_NOT_FOUND
            );
        }

        return sendResponse(
            true,
            __('common.success'),
            new CatalogResource($catalog),
            HttpStatus::HTTP_OK
        );
    }

    public function update(ManufacturerCatalogStoreRequest $request, $catalog_id)
    {
        $validated = $request->validated();

        $catalog = Catalog::where('id', $catalog_id)->where('user_id', $request->user()->id)->first();

        if (! $catalog) {
            return sendResponse(
                false,
                __('common.not_found'),
                null,
                HttpStatus::HTTP_NOT_FOUND
            );
        }

        if ($request->hasFile('catalog')) {
            $file = $request->file('catalog');
            $file_path = $this->storeCatalogFile($file, $validated['name']);

            if (Storage::disk('public')->exists($catalog->file_path)) {
                Storage::disk('public')->delete($catalog->file_path);
            }
        }

        $catalog->update([
            'file_path' => $file_path ?? $catalog->file_path,
            'name' => $validated['name'],
            'file_size' => isset($file) ? $file->getSize() : $catalog->file_size,
            'status' => $validated['status'] ?? $catalog->status,
        ]);

        $translatableChanged = array_intersect_key(
            [
                'name' => $validated['name'],
            ],
            array_flip($catalog->translatableFields())
        );

        if (! empty($translatableChanged)) {
            $catalog->autoTranslate(
                sourceData: $translatableChanged,
                sourceLocale: $request->input('locale'),
            );
        }

        return sendResponse(
            true,
            __('common.updated'),
            new CatalogResource($catalog),
            HttpStatus::HTTP_OK,
        );
    }

    public function destroy(Request $request, $catalog_id)
    {
        //
        $catalog = Catalog::where('id', $catalog_id)->where('user_id', $request->user()->id)->first();
        if (! $catalog) {
            return sendResponse(
                false,
                __('common.not_found'),
                null,
                HttpStatus::HTTP_NOT_FOUND
            );
        }
        $catalog->delete();

        if (Storage::disk('public')->exists($catalog->file_path)) {
            Storage::disk('public')->delete($catalog->file_path);
        }

        return sendResponse(
            true,
            __('common.deleted'),
            null,
            HttpStatus::HTTP_OK
        );
    }

    public function changeStatus(Request $request, $catalog_id)
    {
        $request->validate([
            'status' => 'required|in:active,inactive,draft',
        ]);
        $catalog = Catalog::where('id', $catalog_id)
            ->where('user_id', $request->user()->id)
            ->first();
        if (! $catalog) {
            return sendResponse(
                false,
                __('common.not_found'),
                null,
                HttpStatus::HTTP_NOT_FOUND
            );
        }
        $catalog->update([
            'status' => $request->status,
        ]);

        return sendResponse(
            true,
            __('common.updated'),
            new CatalogResource($catalog),
            HttpStatus::HTTP_OK
        );
    }

    public function stats(Request $request)
    {

        $catalogs = Catalog::where('user_id', $request->user()->id)->get();

        $totalCatalogs = $catalogs->count();
        $activeCatalogs = $catalogs->where('status', 'active')->count();
        $inactiveCatalogs = $catalogs->where('status', 'inactive')->count();
        $draftCatalogs = $catalogs->where('status', 'draft')->count();

        return sendResponse(
            true,
            __('common.success'),
            [
                'total_catalogs' => $totalCatalogs,
                'active_catalogs' => $activeCatalogs,
                'inactive_catalogs' => $inactiveCatalogs,
                'draft_catalogs' => $draftCatalogs,
            ],
            HttpStatus::HTTP_OK
        );

    }

    private function storeCatalogFile(UploadedFile $file, string $name): string
    {
        $safeName = Str::slug($name) ?: Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
        $fileName = $safeName.'_'.time().random_int(1000, 9999).'.'.$file->getClientOriginalExtension();

        return $file->storeAs('catalogs', $fileName, 'public');
    }
}

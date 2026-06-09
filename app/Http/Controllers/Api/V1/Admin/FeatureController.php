<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\FeatureCollection;
use App\Http\Resources\Api\V1\FeatureResource;
use App\Models\Feature;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as HttpStatus;

class FeatureController extends Controller
{
    //

    public function index()
    {
        $features = Feature::all();

        return sendResponse(
            status: true,
            message: __('common.success'),
            data: new FeatureCollection($features),
            statusCode: HttpStatus::HTTP_OK
        );
    }

    public function update(Request $request,  $feature_id)
    {
        $data =  $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $feature = Feature::find($feature_id);
        if (!$feature) {
            return sendResponse(
                status: false,
                message: __('common.not_found'),
                data: null,
                statusCode: HttpStatus::HTTP_NOT_FOUND
            );
        }
        $feature->update($data);


        $translatableChanged = array_intersect_key(
            $data,
            array_flip($feature->translatableFields())
        );

        if (! empty($translatableChanged)) {
            $feature->autoTranslate(
                sourceData: $translatableChanged,
                sourceLocale: $request->locale ?? null,
            );
        }


        return sendResponse(
            status: true,
            message: __('common.updated'),
            data: new FeatureResource($feature),
            statusCode: HttpStatus::HTTP_OK
        );
    }
}

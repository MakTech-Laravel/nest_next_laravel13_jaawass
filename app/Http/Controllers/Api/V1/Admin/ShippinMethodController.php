<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\ShippingMethodStoreRequest;
use App\Http\Resources\Api\V1\Admin\ShippingMethodResource;
use App\Models\ShippingMethod;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as HttpStatus;
class ShippinMethodController extends Controller
{
    //

    public function index()
    {
        $shippingMethods = ShippingMethod::limit(10)->get();

        return sendResponse(
            status: true, 
            message: __('common.success'),
            data: ShippingMethodResource::collection($shippingMethods),
            statusCode: HttpStatus::HTTP_OK
        );
    }

    public function store(ShippingMethodStoreRequest $request)
    {
        $validated = $request->validated();
        
        $shippingMethod = ShippingMethod::create($validated);
        
        $shippingMethod->autoTranslate([
            'name' => $request->name,
        ], $request->locale ?? null);
        
        return sendResponse(
            status: true, 
            message: __('common.success'),
            data: new ShippingMethodResource($shippingMethod),
            statusCode: HttpStatus::HTTP_CREATED
        );
    }

    public function show($id)
    {
       $shippingMethod = ShippingMethod::find($id);

       if(!$shippingMethod){
        return sendResponse(
            status: false, 
            message: __('common.not_found'),
            data: null,
            statusCode: HttpStatus::HTTP_NOT_FOUND
        );
       }

       return sendResponse(
           status: true, 
           message: __('common.success'),
           data: new ShippingMethodResource($shippingMethod),
           statusCode: HttpStatus::HTTP_OK
       );
    }

    public function update($id, Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:shipping_methods,name,' . $id,
            "status" => "nullable|boolean",
        ]);
        
        $shippingMethod = ShippingMethod::find($id);

        if(!$shippingMethod){
            return sendResponse(
                status: false, 
                message: __('common.not_found'),
                data: null,
                statusCode: HttpStatus::HTTP_NOT_FOUND
            );
        }

        $shippingMethod->update([
            'name' => $request->name,
            'status' => $request->status,
        ]);
        $translatableChanged = array_intersect_key(
           [
            'name' => $request->name,
           ],
            array_flip($shippingMethod->translatableFields())
        );

        if (! empty($translatableChanged)) {
            $shippingMethod->autoTranslate(
                sourceData: $translatableChanged,
                sourceLocale: $request->locale ?? null,
            );
        }


        return sendResponse(
            status: true, 
            message: __('common.success'),
            data: new ShippingMethodResource($shippingMethod),
            statusCode: HttpStatus::HTTP_OK
        );
       
    }

    public function destroy($id)
    {
        $shippingMethod = ShippingMethod::find($id);

        if(!$shippingMethod){
            return sendResponse(
                status: false, 
                message: __('common.not_found'),
                data: null,
                statusCode: HttpStatus::HTTP_NOT_FOUND
            );
        }

        $shippingMethod->delete();

        return sendResponse(
            status: true, 
            message: __('common.success'),
            data: new ShippingMethodResource($shippingMethod),
            statusCode: HttpStatus::HTTP_OK
        );
    }


    public function getActiveShippingMethods()
    {
        $shippingMethods = ShippingMethod::where('status', true)->get();

        return sendResponse(
            status: true, 
            message: __('common.success'),
            data: ShippingMethodResource::collection($shippingMethods),
            statusCode: HttpStatus::HTTP_OK
        );
    }
}

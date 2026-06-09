<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\FaqStoreRequest;
use App\Http\Resources\Api\V1\FaqResource;
use App\Models\Faq;
use App\Models\FaqClick;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request as RequestFacade;
use Symfony\Component\HttpFoundation\Response as HttpStatus;

class FaqController extends Controller
{
    //

    public function store(FaqStoreRequest $request)
    {
        $data = [
            'question' => $request->question,
            'answer' => $request->answer,
            'faq_category_id' => $request->faq_category_id,
        ];

        $faq = Faq::create($data);

        $sourceLocale = $request->input('locale') ?? app()->getLocale();
        $sourceData = [
            'question' => $request->question,
            'answer' => $request->answer,
        ];

        // Ensure source-locale row is persisted even when queue workers are not running.
        $faq->upsertTranslations([
            $sourceLocale => $sourceData,
        ]);

        $faq->autoTranslate(
            sourceData: $sourceData,
            sourceLocale: $sourceLocale,
        );

        $faq->load('category');

        return sendResponse(
            status: true,
            message: __('common.created'),
            data: new FaqResource($faq),
            statusCode: HttpStatus::HTTP_CREATED
        );
    }

    public function show($id)
    {
        $faq = Faq::find($id);

        if (! $faq) {
            return sendResponse(
                status: false,
                message: __('common.not_found'),
                data: null,
                statusCode: HttpStatus::HTTP_NOT_FOUND
            );
        }

        $faq->load('category');

        return sendResponse(
            status: true,
            message: __('common.success'),
            data: new FaqResource($faq),
            statusCode: HttpStatus::HTTP_OK
        );
    }

    public function update(FaqStoreRequest $request, $id)
    {
        $faq = Faq::find($id);
        if (! $faq) {
            return sendResponse(
                status: false,
                message: __('common.not_found'),
                data: null,
                statusCode: HttpStatus::HTTP_NOT_FOUND
            );
        }

        $data = $request->validated();

        $faq->update($data);

        $translatableChanged = array_intersect_key(
            $request->validated(),
            array_flip($faq->translatableFields())
        );

        if (! empty($translatableChanged)) {
            $sourceLocale = $request->input('locale') ?? app()->getLocale();

            // Ensure source-locale row is persisted even when queue workers are not running.
            $faq->upsertTranslations([
                $sourceLocale => $translatableChanged,
            ]);

            $faq->autoTranslate(
                sourceData: $translatableChanged,
                sourceLocale: $sourceLocale,
            );
        }

        $faq->load('category');

        return sendResponse(
            status: true,
            message: __('common.success'),
            data: new FaqResource($faq->fresh()),
            statusCode: HttpStatus::HTTP_OK
        );
    }

    public function destroy($id)
    {
        $faq = Faq::find($id);
        if (! $faq) {
            return sendResponse(
                status: false,
                message: __('common.not_found'),
                data: null,
                statusCode: HttpStatus::HTTP_NOT_FOUND
            );
        }

        $faq->load('category');

        $faqData = new FaqResource($faq);
        $faq->delete();

        return sendResponse(
            status: true,
            message: __('common.deleted'),
            data: $faqData,
            statusCode: HttpStatus::HTTP_OK
        );
    }

    public function clicked($id)
    {
        $faq = Faq::find($id);
        if (! $faq) {
            return sendResponse(
                status: false,
                message: __('common.not_found'),
                data: null,
                statusCode: HttpStatus::HTTP_NOT_FOUND
            );
        }

        $request = RequestFacade::instance();
        $ip = $request->ip();
        $userAgent = $request->userAgent();

        FaqClick::create([
            'faq_id' => $faq->id,
            'user_id' => Auth::id(),
            'ip_address' => $ip,
            'user_agent' => $userAgent,
        ]);

        $faq->load('category');

        return sendResponse(
            status: true,
            message: __('common.clicked'),
            data: new FaqResource($faq),
            statusCode: HttpStatus::HTTP_OK
        );
    }

    public function faqPosition(Request $request, $id)
    {
        $faq = Faq::find($id);
        if (! $faq) {
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
            'faq_category_id' => 'required|integer',
        ], [
            'current_position.required' => 'Current position is required',
            'new_position.required' => 'New position is required',
            'faq_category_id.required' => 'FAQ category ID is required',
        ]);

        if ($request->current_position > $request->new_position) {
            // Move up
            Faq::where('sort', '>=', $request->new_position)->where('sort', '<', $request->current_position)->where('faq_category_id', $request->faq_category_id)->increment('sort');
        } else {
            // Move down
            Faq::where('sort', '>', $request->current_position)->where('sort', '<=', $request->new_position)->where('faq_category_id', $request->faq_category_id)->decrement('sort');
        }

        $faq->update([
            'sort' => $request->new_position,
        ]);

        return sendResponse(
            status: true,
            message: __('common.position_updated'),
            data: new FaqResource($faq),
            statusCode: HttpStatus::HTTP_OK
        );
    }
}

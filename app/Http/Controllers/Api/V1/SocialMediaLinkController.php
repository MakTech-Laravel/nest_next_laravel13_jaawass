<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\SocialMediaLinkResource;
use App\Models\SocialMediaLink;
use Symfony\Component\HttpFoundation\Response as HttpStatus;

class SocialMediaLinkController extends Controller
{
    public function index()
    {
        $links = SocialMediaLink::query()
            ->where('enabled', true)
            ->orderBy('sort')
            ->get();

        return sendResponse(
            status: true,
            message: __('common.success'),
            data: SocialMediaLinkResource::collection($links),
            statusCode: HttpStatus::HTTP_OK,
        );
    }
}

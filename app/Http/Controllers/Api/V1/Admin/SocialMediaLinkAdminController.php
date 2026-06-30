<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\StoreSocialMediaLinkRequest;
use App\Http\Requests\Api\V1\Admin\SyncSocialMediaLinksRequest;
use App\Http\Requests\Api\V1\Admin\UpdateSocialMediaLinkRequest;
use App\Http\Resources\Api\V1\SocialMediaLinkResource;
use App\Models\SocialMediaLink;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response as HttpStatus;

class SocialMediaLinkAdminController extends Controller
{
    public function index()
    {
        $links = SocialMediaLink::query()
            ->orderBy('sort')
            ->get();

        return sendResponse(
            status: true,
            message: __('common.success'),
            data: SocialMediaLinkResource::collection($links),
            statusCode: HttpStatus::HTTP_OK,
        );
    }

    public function store(StoreSocialMediaLinkRequest $request)
    {
        $validated = $request->validated();
        $sort = $validated['sort'] ?? ((int) SocialMediaLink::query()->max('sort') + 1);

        $link = SocialMediaLink::query()->create([
            'platform' => $validated['platform'],
            'icon' => $validated['icon'],
            'url' => $validated['url'],
            'enabled' => $validated['enabled'] ?? true,
            'sort' => $sort,
        ]);

        return sendResponse(
            status: true,
            message: __('social_media_links.created'),
            data: new SocialMediaLinkResource($link),
            statusCode: HttpStatus::HTTP_CREATED,
        );
    }

    public function update(UpdateSocialMediaLinkRequest $request, int $socialMediaLink)
    {
        $link = SocialMediaLink::query()->find($socialMediaLink);

        if ($link === null) {
            return sendResponse(
                status: false,
                message: __('common.not_found'),
                data: null,
                statusCode: HttpStatus::HTTP_NOT_FOUND,
            );
        }

        $link->update($request->validated());

        return sendResponse(
            status: true,
            message: __('social_media_links.updated'),
            data: new SocialMediaLinkResource($link->fresh()),
            statusCode: HttpStatus::HTTP_OK,
        );
    }

    public function destroy(int $socialMediaLink)
    {
        $link = SocialMediaLink::query()->find($socialMediaLink);

        if ($link === null) {
            return sendResponse(
                status: false,
                message: __('common.not_found'),
                data: null,
                statusCode: HttpStatus::HTTP_NOT_FOUND,
            );
        }

        $link->delete();

        return sendResponse(
            status: true,
            message: __('social_media_links.deleted'),
            data: null,
            statusCode: HttpStatus::HTTP_OK,
        );
    }

    public function sync(SyncSocialMediaLinksRequest $request)
    {
        $links = $request->validated()['links'];

        $synced = DB::transaction(function () use ($links) {
            $keptIds = [];

            foreach ($links as $index => $linkData) {
                $sort = (int) ($linkData['sort'] ?? ($index + 1));
                $payload = [
                    'platform' => $linkData['platform'],
                    'icon' => $linkData['icon'],
                    'url' => $linkData['url'],
                    'enabled' => (bool) $linkData['enabled'],
                    'sort' => $sort,
                ];

                if (! empty($linkData['id'])) {
                    $link = SocialMediaLink::query()->find($linkData['id']);
                    if ($link !== null) {
                        $link->update($payload);
                        $keptIds[] = $link->id;

                        continue;
                    }
                }

                $link = SocialMediaLink::query()->create($payload);
                $keptIds[] = $link->id;
            }

            if ($keptIds !== []) {
                SocialMediaLink::query()->whereNotIn('id', $keptIds)->delete();
            } else {
                SocialMediaLink::query()->delete();
            }

            return SocialMediaLink::query()->orderBy('sort')->get();
        });

        return sendResponse(
            status: true,
            message: __('social_media_links.synced'),
            data: SocialMediaLinkResource::collection($synced),
            statusCode: HttpStatus::HTTP_OK,
        );
    }
}

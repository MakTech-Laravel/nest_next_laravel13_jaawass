<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\SendPlatformTestEmailRequest;
use App\Http\Requests\Api\V1\Admin\UpdatePlatformSettingsRequest;
use App\Jobs\SendMailJob;
use App\Services\Platform\PlatformSettingsService;
use Illuminate\Support\Facades\Queue;
use Symfony\Component\HttpFoundation\Response as HttpStatus;

class AdminPlatformSettingsController extends Controller
{
    public function __construct(
        private readonly PlatformSettingsService $settingsService,
    ) {}

    public function show()
    {
        return sendResponse(
            status: true,
            message: __('platform_settings.show_success'),
            data: $this->settingsService->all(),
            statusCode: HttpStatus::HTTP_OK,
        );
    }

    public function update(UpdatePlatformSettingsRequest $request)
    {
        $settings = $this->settingsService->update(
            $request->user(),
            $request->validated(),
        );

        return sendResponse(
            status: true,
            message: __('platform_settings.updated'),
            data: $settings,
            statusCode: HttpStatus::HTTP_OK,
        );
    }

    public function sendTestEmail(SendPlatformTestEmailRequest $request)
    {
        $settings = $this->settingsService->all();
        $recipient = $request->validated('recipient') ?? $request->user()->email;

        Queue::push(new SendMailJob(
            recipient: $recipient,
            template: 'admin-test-email',
            data: [
                'recipient_name' => trim($request->user()->first_name.' '.$request->user()->last_name) ?: 'Admin',
                'from_name' => $settings['email']['from_name'] ?? config('mail.from.name'),
                'from_email' => $settings['email']['from_email'] ?? config('mail.from.address'),
                'platform_name' => $settings['general']['platform_name'] ?? config('app.name'),
            ],
        ));

        return sendResponse(
            status: true,
            message: __('platform_settings.test_email_queued'),
            data: ['recipient' => $recipient],
            statusCode: HttpStatus::HTTP_OK,
        );
    }
}

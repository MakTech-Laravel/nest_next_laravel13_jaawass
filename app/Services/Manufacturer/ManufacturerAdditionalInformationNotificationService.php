<?php

namespace App\Services\Manufacturer;

use App\Enums\MailTemplate;
use App\Jobs\Support\SendSupportTicketInAppNotificationJob;
use App\Models\ManufacturerAdditionalInformationRequest;
use App\Models\User;
use App\Services\Mailing\MailingService;
use App\Support\Mail\MailNotificationHelper;

class ManufacturerAdditionalInformationNotificationService
{
    public function __construct(
        private readonly MailingService $mailingService,
    ) {}

    public function notifyAdminOfSubmission(ManufacturerAdditionalInformationRequest $request): void
    {
        $request->loadMissing(['manufacturer.company', 'requestedBy', 'responses']);

        $admin = $request->requestedBy;
        $manufacturer = $request->manufacturer;

        if ($admin === null || $manufacturer === null) {
            return;
        }

        $mailData = $this->mailData($request, $manufacturer);

        if ($admin->email !== null && $admin->email !== '') {
            $this->mailingService->send(
                $admin->email,
                MailTemplate::AdminManufacturerAdditionalInformationResponse,
                $mailData,
            );
        }

        MailNotificationHelper::sendIfEmail($manufacturer, function (string $email) use ($mailData): void {
            $this->mailingService->send(
                $email,
                MailTemplate::ManufacturerAdditionalInformationReceived,
                $mailData,
            );
        });

        $this->dispatchInAppNotification($request, $admin, $manufacturer, $mailData);
    }

    /**
     * @return array<string, mixed>
     */
    private function mailData(
        ManufacturerAdditionalInformationRequest $request,
        User $manufacturer,
    ): array {
        $companyName = $manufacturer->company?->company_name ?? config('app.name');
        $referenceId = sprintf('SN-MFR-%06d', $request->id);

        return [
            'adminName' => $this->displayName($request->requestedBy),
            'manufacturerName' => $this->displayName($manufacturer),
            'companyName' => $companyName,
            'referenceId' => $referenceId,
            'submittedAt' => $request->submitted_at?->format('F j, Y g:i A') ?? now()->format('F j, Y g:i A'),
            'responseCount' => $request->responses->count(),
            'responses' => $request->responses
                ->map(fn ($response): array => [
                    'typeLabel' => $response->type->label(),
                    'message' => $response->message,
                    'fileName' => $response->original_name,
                ])
                ->values()
                ->all(),
            'reviewUrl' => $this->reviewUrl($request),
            'dashboardUrl' => MailNotificationHelper::frontendUrl('dashboard/manufacturer'),
        ];
    }

    /**
     * @param  array<string, mixed>  $mailData
     */
    private function dispatchInAppNotification(
        ManufacturerAdditionalInformationRequest $request,
        User $admin,
        User $manufacturer,
        array $mailData,
    ): void {
        $companyName = (string) ($mailData['companyName'] ?? config('app.name'));

        SendSupportTicketInAppNotificationJob::dispatch(
            recipientId: $admin->id,
            type: 'manufacturer.additional_information.submitted',
            title: __('manufacturer_additional_information.admin_notification_title'),
            body: __('manufacturer_additional_information.admin_notification_body', [
                'company' => $companyName,
                'reference' => $mailData['referenceId'],
            ]),
            data: [
                'category' => 'manufacturer_review',
                'request_id' => $request->id,
                'manufacturer_id' => $manufacturer->id,
                'ticket_id' => $request->ticket_id,
                'reference_id' => $mailData['referenceId'],
                'response_count' => $mailData['responseCount'],
            ],
            actionUrl: $mailData['reviewUrl'],
            senderId: $manufacturer->id,
        );
    }

    private function reviewUrl(ManufacturerAdditionalInformationRequest $request): string
    {
        $frontendUrl = rtrim((string) config('app.frontend_url', config('app.url')), '/');

        if ($request->ticket_id !== null) {
            return "{$frontendUrl}/admin/customer-supports/tickets/{$request->ticket_id}";
        }

        $manufacturerId = $request->user_id;

        return "{$frontendUrl}/admin/manufacturer-registrations?manufacturer={$manufacturerId}";
    }

    private function displayName(?User $user): string
    {
        if ($user === null) {
            return 'there';
        }

        $name = trim($user->first_name.' '.$user->last_name);

        return $name !== '' ? $name : 'there';
    }
}

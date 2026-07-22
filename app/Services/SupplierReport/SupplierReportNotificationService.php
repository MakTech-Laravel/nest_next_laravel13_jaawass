<?php

namespace App\Services\SupplierReport;

use App\Enums\MailTemplate;
use App\Enums\SupplierReportStatus;
use App\Jobs\Support\SendSupportTicketInAppNotificationJob;
use App\Models\SupplierReport;
use App\Models\SupplierReportStatusLog;
use App\Models\User;
use App\Services\Mailing\MailingService;
use App\Support\Mail\MailNotificationHelper;

class SupplierReportNotificationService
{
    public function __construct(
        private readonly MailingService $mailingService,
    ) {}

    public function sendReportReceived(SupplierReport $report): void
    {
        $reporter = $report->reporter;

        if ($reporter === null || $reporter->email === null) {
            return;
        }

        $data = $this->reportMailData($report);

        $this->mailingService->send(
            $reporter->email,
            MailTemplate::SupplierReportReceived,
            [
                'buyerName' => $data['buyerName'],
                'supplierName' => $data['supplierName'],
                'reportId' => $data['reportId'],
                'messageBody' => nl2br(e((string) $report->details)),
                'reasonLabel' => $data['reasonLabel'],
                'reportsUrl' => $data['reportsUrl'],
            ],
        );

        SendSupportTicketInAppNotificationJob::dispatch(
            recipientId: $reporter->id,
            type: 'supplier.report.received',
            title: __('mail.supplier_report_received.notification_title'),
            body: __('mail.supplier_report_received.notification_body', [
                'supplier' => $data['supplierName'],
            ]),
            data: ['report_id' => $report->id],
            actionUrl: $data['reportsUrl'],
            senderId: null,
        );

        $this->notifyAdminsOfNewReport($report, $data);
    }

    public function sendStatusUpdated(SupplierReport $report, SupplierReportStatusLog $log): void
    {
        $reporter = $report->reporter;

        if ($reporter === null || $reporter->email === null) {
            return;
        }

        $toStatus = $log->to_status instanceof SupplierReportStatus
            ? $log->to_status
            : SupplierReportStatus::from((string) $log->to_status);

        $data = $this->reportMailData($report);

        $this->mailingService->send(
            $reporter->email,
            MailTemplate::SupplierReportStatusUpdated,
            [
                'buyerName' => $data['buyerName'],
                'supplierName' => $data['supplierName'],
                'statusLabel' => $toStatus->label(),
                'messageBody' => $log->message ? nl2br(e((string) $log->message)) : null,
                'reportsUrl' => $data['reportsUrl'],
            ],
        );

        SendSupportTicketInAppNotificationJob::dispatch(
            recipientId: $reporter->id,
            type: 'supplier.report.status',
            title: __('mail.supplier_report_status_updated.notification_title'),
            body: __('mail.supplier_report_status_updated.notification_body', [
                'supplier' => $data['supplierName'],
                'status' => $toStatus->label(),
            ]),
            data: ['report_id' => $report->id, 'status' => $toStatus->value],
            actionUrl: $data['reportsUrl'],
            senderId: null,
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function notifyAdminsOfNewReport(SupplierReport $report, array $data): void
    {
        $adminUrl = MailNotificationHelper::frontendUrl('admin/reports');

        foreach (MailNotificationHelper::adminRecipients() as $admin) {
            MailNotificationHelper::sendIfEmail($admin, function (string $email) use ($data, $report, $adminUrl): void {
                $this->mailingService->send($email, MailTemplate::SupplierReportReceivedAdmin, [
                    'buyerName' => $data['buyerName'],
                    'supplierName' => $data['supplierName'],
                    'reportId' => $data['reportId'],
                    'reasonLabel' => $data['reasonLabel'],
                    'messageBody' => $report->details ? nl2br(e((string) $report->details)) : null,
                    'ctaUrl' => $adminUrl,
                    'referenceId' => 'RPT-'.str_pad((string) $report->id, 5, '0', STR_PAD_LEFT),
                ]);
            }, 'supplier.report.created');

            SendSupportTicketInAppNotificationJob::dispatch(
                recipientId: $admin->id,
                type: 'supplier.report.created',
                title: __('mail.supplier_report_received_admin.notification_title'),
                body: __('mail.supplier_report_received_admin.notification_body', [
                    'buyer' => $data['buyerName'],
                    'supplier' => $data['supplierName'],
                ]),
                data: ['report_id' => $report->id],
                actionUrl: $adminUrl,
                senderId: $report->reporter_id,
            );
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function reportMailData(SupplierReport $report): array
    {
        $supplierName = $report->supplier?->company?->company_name
            ?? trim(($report->supplier?->first_name ?? '').' '.($report->supplier?->last_name ?? ''))
            ?: __('supplier_report.supplier');

        $reason = $report->reason instanceof \App\Enums\SupplierReportReason
            ? $report->reason
            : \App\Enums\SupplierReportReason::from((string) $report->reason);

        return [
            'buyerName' => $this->displayName($report->reporter),
            'supplierName' => $supplierName,
            'reasonLabel' => $reason->label(),
            'reportId' => $report->id,
            'details' => $report->details,
            'reportsUrl' => $this->reportsUrl(),
        ];
    }

    private function displayName(?User $user): string
    {
        return MailNotificationHelper::displayName($user);
    }

    private function reportsUrl(): string
    {
        return MailNotificationHelper::frontendUrl('dashboard/buyer/reports');
    }
}

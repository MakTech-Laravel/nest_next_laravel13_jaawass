<?php

namespace App\Services\SupplierReport;

use App\Enums\MailTemplate;
use App\Enums\SupplierReportStatus;
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
            array_merge($data, [
                'preheader' => __('mail.supplier_report_received.preheader'),
                'headerTitle' => __('mail.supplier_report_received.subject'),
                'headerSubtitle' => __('mail.layout.default_eyebrow'),
                'intro' => __('mail.supplier_report_received.intro', [
                    'name' => $data['buyerName'],
                    'supplier' => $data['supplierName'],
                    'id' => $data['reportId'],
                ]),
                'messageHeading' => __('mail.supplier_report_received.details_heading'),
                'messageBody' => nl2br(e((string) $report->details)),
                'detailsHeading' => __('mail.supplier_report_received.reason_heading'),
                'details' => ['Reason' => $data['reasonLabel']],
                'ctaUrl' => $data['reportsUrl'],
                'ctaLabel' => __('mail.supplier_report_received.cta'),
                'footerNote' => __('mail.supplier_report_received.footer'),
            ]),
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
            array_merge($data, [
                'preheader' => __('mail.supplier_report_status_updated.preheader'),
                'headerTitle' => __('mail.supplier_report_status_updated.subject'),
                'headerSubtitle' => $toStatus->label(),
                'intro' => __('mail.supplier_report_status_updated.intro', [
                    'name' => $data['buyerName'],
                    'supplier' => $data['supplierName'],
                    'status' => $toStatus->label(),
                ]),
                'messageHeading' => __('mail.supplier_report_status_updated.message_heading'),
                'messageBody' => $log->message ? nl2br(e((string) $log->message)) : null,
                'ctaUrl' => $data['reportsUrl'],
                'ctaLabel' => __('mail.supplier_report_status_updated.cta'),
                'footerNote' => __('mail.supplier_report_status_updated.footer'),
            ]),
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function notifyAdminsOfNewReport(SupplierReport $report, array $data): void
    {
        $adminUrl = MailNotificationHelper::frontendUrl('admin/supplier-reports/'.$report->id);

        foreach (MailNotificationHelper::adminRecipients() as $admin) {
            MailNotificationHelper::sendIfEmail($admin, function (string $email) use ($data, $report, $adminUrl): void {
                $this->mailingService->send($email, MailTemplate::SupplierReportReceivedAdmin, [
                    'preheader' => __('mail.supplier_report_received_admin.preheader'),
                    'headerTitle' => __('mail.supplier_report_received_admin.header_title'),
                    'headerSubtitle' => __('mail.supplier_report_received_admin.header_subtitle'),
                    'intro' => __('mail.supplier_report_received_admin.intro', [
                        'buyer' => $data['buyerName'],
                        'supplier' => $data['supplierName'],
                        'id' => $data['reportId'],
                    ]),
                    'detailsHeading' => __('mail.supplier_report_received_admin.details_heading'),
                    'details' => [
                        'Reason' => $data['reasonLabel'],
                        'Report ID' => (string) $data['reportId'],
                    ],
                    'messageHeading' => __('mail.supplier_report_received.details_heading'),
                    'messageBody' => $report->details ? nl2br(e((string) $report->details)) : null,
                    'ctaUrl' => $adminUrl,
                    'ctaLabel' => __('mail.supplier_report_received_admin.cta'),
                    'referenceId' => 'RPT-'.str_pad((string) $report->id, 5, '0', STR_PAD_LEFT),
                    'footerNote' => __('mail.supplier_report_received_admin.footer'),
                ]);
            });
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

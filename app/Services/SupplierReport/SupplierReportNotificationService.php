<?php

namespace App\Services\SupplierReport;

use App\Enums\MailTemplate;
use App\Enums\SupplierReportStatus;
use App\Models\SupplierReport;
use App\Models\SupplierReportStatusLog;
use App\Models\User;
use App\Services\Mailing\MailingService;

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

        $this->mailingService->send(
            $reporter->email,
            MailTemplate::SupplierReportReceived,
            $this->reportMailData($report),
        );
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

        $this->mailingService->send(
            $reporter->email,
            MailTemplate::SupplierReportStatusUpdated,
            array_merge($this->reportMailData($report), [
                'statusLabel' => $toStatus->label(),
                'statusValue' => $toStatus->value,
                'adminMessage' => $log->message,
                'reportsUrl' => $this->reportsUrl(),
            ]),
        );
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
        if ($user === null) {
            return 'there';
        }

        $name = trim($user->first_name.' '.$user->last_name);

        return $name !== '' ? $name : 'there';
    }

    private function reportsUrl(): string
    {
        $frontendUrl = rtrim((string) config('app.frontend_url', config('app.url')), '/');

        return $frontendUrl.'/dashboard/buyer/reports';
    }
}

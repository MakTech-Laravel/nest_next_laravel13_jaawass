<?php

namespace App\Services\SupplierReport;

use App\Enums\SupplierReportPriority;
use App\Enums\SupplierReportReason;
use App\Enums\SupplierReportStatus;
use App\Enums\UserRole;
use App\Models\SupplierReport;
use App\Models\SupplierReportStatusLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SupplierReportService
{
    public function __construct(
        private readonly SupplierReportNotificationService $notificationService,
    ) {}

    /**
     * @param  array{reason: string, details?: string|null, source_page?: string|null}  $payload
     */
    public function create(User $reporter, User $supplier, array $payload): SupplierReport
    {
        $this->assertCanReport($reporter, (int) $supplier->id);

        $reason = SupplierReportReason::from($payload['reason']);

        $report = SupplierReport::query()->create([
            'reporter_id' => $reporter->id,
            'supplier_id' => $supplier->id,
            'reason' => $reason->value,
            'details' => $payload['details'] ?? null,
            'status' => SupplierReportStatus::Open->value,
            'priority' => SupplierReportPriority::Medium->value,
            'source_page' => $payload['source_page'] ?? null,
            'metadata' => $payload['metadata'] ?? null,
        ]);

        $report->load(['reporter', 'supplier.company']);

        $this->notificationService->sendReportReceived($report);

        return $report;
    }

    public function assertCanReport(User $reporter, int $supplierId): void
    {
        if ((int) $reporter->id === $supplierId) {
            throw ValidationException::withMessages([
                'supplier' => [__('supplier_report.cannot_report_self')],
            ]);
        }

        $supplier = User::query()
            ->where('id', $supplierId)
            ->where('role', UserRole::MANUFACTURER->value)
            ->first();

        if ($supplier === null) {
            throw ValidationException::withMessages([
                'supplier' => [__('supplier_report.supplier_not_found')],
            ]);
        }

        $hasOpenReport = SupplierReport::query()
            ->where('reporter_id', $reporter->id)
            ->where('supplier_id', $supplierId)
            ->whereIn('status', [
                SupplierReportStatus::Open->value,
                SupplierReportStatus::Investigating->value,
            ])
            ->exists();

        if ($hasOpenReport) {
            throw ValidationException::withMessages([
                'supplier' => [__('supplier_report.already_reported')],
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function adminListQuery(array $filters): Builder
    {
        $query = SupplierReport::query()
            ->with(['reporter', 'supplier.company', 'assignee'])
            ->latest('id');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        if (! empty($filters['supplier_id'])) {
            $query->where('supplier_id', (int) $filters['supplier_id']);
        }

        if (! empty($filters['reporter_id'])) {
            $query->where('reporter_id', (int) $filters['reporter_id']);
        }

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);

            $query->where(function (Builder $builder) use ($search): void {
                $builder
                    ->where('details', 'like', "%{$search}%")
                    ->orWhereHas('reporter', function (Builder $reporterQuery) use ($search): void {
                        $reporterQuery
                            ->where('email', 'like', "%{$search}%")
                            ->orWhere('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('supplier', function (Builder $supplierQuery) use ($search): void {
                        $supplierQuery
                            ->where('email', 'like', "%{$search}%")
                            ->orWhereHas('company', fn (Builder $companyQuery) => $companyQuery
                                ->where('company_name', 'like', "%{$search}%"));
                    });
            });
        }

        return $query;
    }

    /**
     * @param  array{status?: string, priority?: string, assigned_to?: int|null, message?: string|null}  $payload
     */
    public function updateByAdmin(SupplierReport $report, User $admin, array $payload): SupplierReport
    {
        return DB::transaction(function () use ($report, $admin, $payload): SupplierReport {
            $locked = SupplierReport::query()
                ->whereKey($report->id)
                ->lockForUpdate()
                ->firstOrFail();

            $fromStatus = $locked->status instanceof SupplierReportStatus
                ? $locked->status
                : SupplierReportStatus::from((string) $locked->status);

            $updates = [];
            $statusChanged = false;
            $newStatus = $fromStatus;

            if (array_key_exists('priority', $payload) && $payload['priority'] !== null) {
                $updates['priority'] = $payload['priority'];
            }

            if (array_key_exists('assigned_to', $payload)) {
                $updates['assigned_to'] = $payload['assigned_to'];
            }

            if (array_key_exists('status', $payload) && $payload['status'] !== null) {
                $newStatus = SupplierReportStatus::from($payload['status']);
                $updates['status'] = $newStatus->value;
                $statusChanged = $newStatus !== $fromStatus;

                if ($newStatus->isClosed()) {
                    $updates['resolved_by'] = $admin->id;
                    $updates['resolved_at'] = now();
                } else {
                    $updates['resolved_by'] = null;
                    $updates['resolved_at'] = null;
                }
            }

            if ($updates !== []) {
                $locked->update($updates);
            }

            $message = trim((string) ($payload['message'] ?? ''));

            if ($statusChanged || $message !== '') {
                $log = SupplierReportStatusLog::query()->create([
                    'supplier_report_id' => $locked->id,
                    'admin_id' => $admin->id,
                    'from_status' => $fromStatus->value,
                    'to_status' => ($updates['status'] ?? $fromStatus->value),
                    'message' => $message !== '' ? $message : null,
                ]);

                if ($statusChanged) {
                    $locked->load(['reporter', 'supplier.company']);
                    $this->notificationService->sendStatusUpdated($locked->fresh(['reporter', 'supplier.company']), $log);
                }
            }

            return $locked->fresh([
                'reporter',
                'supplier.company',
                'assignee',
                'resolver',
                'statusLogs.admin',
            ]);
        });
    }
}

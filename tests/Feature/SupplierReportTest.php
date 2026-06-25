<?php

use App\Enums\SupplierReportReason;
use App\Enums\SupplierReportStatus;
use App\Enums\UserManuFactureStatus;
use App\Enums\UserRole;
use App\Jobs\SendMailJob;
use App\Models\SupplierReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;
use Tests\TestCase;

uses(RefreshDatabase::class);

beforeEach(function () {
    app(ClientRepository::class)->createPersonalAccessGrantClient(
        name: 'Test Personal Access Client',
        provider: config('auth.guards.api.provider')
    );
});

function createSupplierForReport(): User
{
    return User::factory()->create([
        'role' => UserRole::MANUFACTURER->value,
        'manufacture_status' => UserManuFactureStatus::APPROVED->value,
    ]);
}

test('buyer can report supplier and receives queued acknowledgment email', function () {
    Queue::fake();

    $buyer = User::factory()->create(['role' => UserRole::BUYER->value]);
    $supplier = createSupplierForReport();

    Passport::actingAs($buyer);

    /** @var TestCase $this */
    $response = $this->postJson("/api/v1/buyer/suppliers/{$supplier->id}/reports", [
        'reason' => SupplierReportReason::Scam->value,
        'details' => 'Misleading company profile information.',
        'source_page' => '/suppliers/test-supplier',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.reason', SupplierReportReason::Scam->value)
        ->assertJsonPath('data.status', SupplierReportStatus::Open->value);

    $this->assertDatabaseHas('supplier_reports', [
        'reporter_id' => $buyer->id,
        'supplier_id' => $supplier->id,
        'reason' => SupplierReportReason::Scam->value,
    ]);

    Queue::assertPushed(SendMailJob::class, function (SendMailJob $job) use ($buyer): bool {
        return $job->recipient === $buyer->email
            && $job->template === 'supplier-report-received';
    });
});

test('buyer cannot report same supplier while open report exists', function () {
    Queue::fake();

    $buyer = User::factory()->create(['role' => UserRole::BUYER->value]);
    $supplier = createSupplierForReport();

    Passport::actingAs($buyer);

    /** @var TestCase $this */
    $this->postJson("/api/v1/buyer/suppliers/{$supplier->id}/reports", [
        'reason' => SupplierReportReason::Fake->value,
    ])->assertCreated();

    $this->postJson("/api/v1/buyer/suppliers/{$supplier->id}/reports", [
        'reason' => SupplierReportReason::Other->value,
    ])->assertUnprocessable();
});

test('buyer can report supplier again after previous report is resolved', function () {
    Queue::fake();

    $buyer = User::factory()->create(['role' => UserRole::BUYER->value]);
    $supplier = createSupplierForReport();

    SupplierReport::query()->create([
        'reporter_id' => $buyer->id,
        'supplier_id' => $supplier->id,
        'reason' => SupplierReportReason::Fake->value,
        'status' => SupplierReportStatus::Resolved->value,
        'resolved_at' => now()->subDay(),
    ]);

    Passport::actingAs($buyer);

    /** @var TestCase $this */
    $this->postJson("/api/v1/buyer/suppliers/{$supplier->id}/reports", [
        'reason' => SupplierReportReason::Scam->value,
    ])->assertCreated();
});

test('admin can update report status with message and buyer email is queued', function () {
    Queue::fake();

    $admin = User::factory()->create(['role' => UserRole::ADMIN->value]);
    $buyer = User::factory()->create(['role' => UserRole::BUYER->value]);
    $supplier = createSupplierForReport();

    $report = SupplierReport::query()->create([
        'reporter_id' => $buyer->id,
        'supplier_id' => $supplier->id,
        'reason' => SupplierReportReason::Certification->value,
        'status' => SupplierReportStatus::Open->value,
    ]);

    Passport::actingAs($admin);

    /** @var TestCase $this */
    $response = $this->patchJson("/api/v1/admin/supplier-reports/{$report->id}", [
        'status' => SupplierReportStatus::Resolved->value,
        'priority' => 'high',
        'message' => 'We reviewed the supplier and took appropriate action. Thank you for reporting.',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.status', SupplierReportStatus::Resolved->value);

    $this->assertDatabaseHas('supplier_report_status_logs', [
        'supplier_report_id' => $report->id,
        'admin_id' => $admin->id,
        'to_status' => SupplierReportStatus::Resolved->value,
    ]);

    Queue::assertPushed(SendMailJob::class, function (SendMailJob $job) use ($buyer): bool {
        return $job->recipient === $buyer->email
            && $job->template === 'supplier-report-status-updated';
    });
});

test('admin can list supplier reports', function () {
    $admin = User::factory()->create(['role' => UserRole::ADMIN->value]);
    $buyer = User::factory()->create(['role' => UserRole::BUYER->value]);
    $supplier = createSupplierForReport();

    SupplierReport::query()->create([
        'reporter_id' => $buyer->id,
        'supplier_id' => $supplier->id,
        'reason' => SupplierReportReason::Other->value,
        'status' => SupplierReportStatus::Investigating->value,
    ]);

    Passport::actingAs($admin);

    /** @var TestCase $this */
    $this->getJson('/api/v1/admin/supplier-reports?status=investigating')
        ->assertOk()
        ->assertJsonPath('data.0.status', SupplierReportStatus::Investigating->value);
});

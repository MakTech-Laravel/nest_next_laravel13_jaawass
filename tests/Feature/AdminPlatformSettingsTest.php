<?php

use App\Enums\DatabaseExportScope;
use App\Enums\DatabaseExportStatus;
use App\Enums\DatabaseExportType;
use App\Enums\UserRole;
use App\Jobs\ProcessDatabaseExportJob;
use App\Models\DatabaseExport;
use App\Models\PlatformSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;
use Tests\TestCase;

uses(RefreshDatabase::class);

beforeEach(function () {
    app(ClientRepository::class)->createPersonalAccessGrantClient(
        name: 'Test Personal Access Client',
        provider: config('auth.guards.api.provider')
    );

    Storage::fake('local');
});

function createAdminUser(): User
{
    return User::factory()->create(['role' => UserRole::ADMIN->value]);
}

test('admin can fetch and update platform settings', function () {
    $admin = createAdminUser();
    Passport::actingAs($admin);

    /** @var TestCase $this */
    $this->getJson('/api/v1/admin/settings')
        ->assertOk()
        ->assertJsonPath('data.general.platform_name', 'SourceNest');

    $this->putJson('/api/v1/admin/settings', [
        'general' => [
            'platform_name' => 'SourceNest Pro',
            'support_email' => 'help@sourcenest.com',
        ],
        'security' => [
            'rate_limiting' => false,
        ],
    ])
        ->assertOk()
        ->assertJsonPath('data.general.platform_name', 'SourceNest Pro')
        ->assertJsonPath('data.security.rate_limiting', false);

    $this->assertDatabaseHas('platform_settings', [
        'group' => 'general',
        'updated_by' => $admin->id,
    ]);

    $stored = PlatformSetting::query()->where('group', 'general')->first();
    expect($stored?->payload['platform_name'] ?? null)->toBe('SourceNest Pro');
});

test('admin can queue full database backup export', function () {
    Queue::fake();

    $admin = createAdminUser();
    Passport::actingAs($admin);

    /** @var TestCase $this */
    $response = $this->postJson('/api/v1/admin/database/exports', [
        'type' => DatabaseExportType::Backup->value,
        'scope' => DatabaseExportScope::Full->value,
        'chunk_rows' => 500,
    ])->assertAccepted();

    $exportId = $response->json('data.id');
    expect($exportId)->not->toBeNull();

    Queue::assertPushed(ProcessDatabaseExportJob::class, fn (ProcessDatabaseExportJob $job): bool => $job->exportId === $exportId);

    $this->assertDatabaseHas('database_exports', [
        'id' => $exportId,
        'type' => DatabaseExportType::Backup->value,
        'scope' => DatabaseExportScope::Full->value,
        'status' => DatabaseExportStatus::Pending->value,
        'created_by' => $admin->id,
    ]);
});

test('database export job creates downloadable zip archive', function () {
    $admin = createAdminUser();
    $tables = app(\App\Services\Database\DatabaseSqlExporter::class)->listTables();
    $table = $tables[0] ?? 'users';

    $export = DatabaseExport::query()->create([
        'type' => DatabaseExportType::Export->value,
        'scope' => DatabaseExportScope::Tables->value,
        'tables' => [$table],
        'chunk_rows' => 100,
        'status' => DatabaseExportStatus::Pending,
        'total_tables' => 1,
        'created_by' => $admin->id,
    ]);

    app(\App\Services\Database\DatabaseExportService::class)->process($export->fresh());

    $export->refresh();

    expect($export->status)->toBe(DatabaseExportStatus::Completed)
        ->and($export->storage_path)->not->toBeNull()
        ->and(Storage::disk('local')->exists((string) $export->storage_path))->toBeTrue();
});

test('admin can list database tables for export', function () {
    $admin = createAdminUser();
    Passport::actingAs($admin);

    /** @var TestCase $this */
    $response = $this->getJson('/api/v1/admin/database/tables')
        ->assertOk()
        ->assertJsonStructure([
            'success',
            'data',
        ]);

    $tables = $response->json('data') ?? [];
    foreach ($tables as $table) {
        expect($table)->not->toContain('.');
    }
});

test('database table listing only includes the active connection database', function () {
    $tables = app(\App\Services\Database\DatabaseSqlExporter::class)->listTables();

    expect($tables)->not->toBeEmpty();

    foreach ($tables as $table) {
        expect($table)->not->toContain('.');
    }
});

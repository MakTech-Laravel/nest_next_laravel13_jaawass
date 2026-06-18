<?php

use App\Enums\AdditionalInformationType;
use App\Enums\UserManuFactureStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Jobs\SendMailJob;
use App\Models\ManufacturerAdditionalInformationRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
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
});

test('admin can request additional information and email is queued', function () {
    Queue::fake();
    Storage::fake('public');

    $admin = User::factory()->create(['role' => UserRole::ADMIN->value]);
    $manufacturer = User::factory()->create([
        'role' => UserRole::MANUFACTURER->value,
        'status' => UserStatus::PENDING,
        'manufacture_status' => UserManuFactureStatus::PENDING,
    ]);

    Passport::actingAs($admin);

    /** @var TestCase $this */
    $response = $this->postJson("/api/v1/admin/manufacturer/{$manufacturer->id}/additional-information", [
        'message' => 'Please upload your updated business license.',
        'allowed_types' => ['text', 'document'],
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.message', 'Please upload your updated business license.')
        ->assertJsonPath('data.status', 'pending');

    $this->assertDatabaseHas('manufacturer_additional_information_requests', [
        'user_id' => $manufacturer->id,
        'requested_by' => $admin->id,
        'status' => 'pending',
    ]);

    Queue::assertPushed(SendMailJob::class, function (SendMailJob $job) use ($manufacturer): bool {
        return $job->recipient === $manufacturer->email
            && $job->template === 'manufacturer-additional-information';
    });
});

test('manufacturer can submit additional information via public token', function () {
    Storage::fake('public');

    $admin = User::factory()->create(['role' => UserRole::ADMIN->value]);
    $manufacturer = User::factory()->create([
        'role' => UserRole::MANUFACTURER->value,
        'status' => UserStatus::PENDING,
        'manufacture_status' => UserManuFactureStatus::PENDING,
    ]);

    $request = ManufacturerAdditionalInformationRequest::query()->create([
        'user_id' => $manufacturer->id,
        'requested_by' => $admin->id,
        'token' => 'test-token-123',
        'message' => 'Send factory photos.',
        'allowed_types' => ['text', 'image'],
        'status' => 'pending',
        'expires_at' => now()->addDays(3),
    ]);

    $file = UploadedFile::fake()->image('factory.jpg');

    /** @var TestCase $this */
    $response = $this->post("/api/v1/manufacturer/additional-information/{$request->token}", [
        'responses' => [
            ['type' => 'text', 'message' => 'Here are the requested details.'],
            ['type' => 'image', 'file' => $file],
        ],
    ]);

    $response->assertOk()
        ->assertJsonPath('data.status', 'submitted');

    $request->refresh();
    expect($request->status->value)->toBe('submitted');
    expect($request->responses)->toHaveCount(2);

    Storage::disk('public')->assertExists($request->responses->firstWhere('type', AdditionalInformationType::Image)->file_path);
});

test('admin can list additional information requests for manufacturer', function () {
    $admin = User::factory()->create(['role' => UserRole::ADMIN->value]);
    $manufacturer = User::factory()->create(['role' => UserRole::MANUFACTURER->value]);

    ManufacturerAdditionalInformationRequest::query()->create([
        'user_id' => $manufacturer->id,
        'requested_by' => $admin->id,
        'token' => 'list-token',
        'message' => 'Need more docs',
        'allowed_types' => ['document'],
        'status' => 'pending',
        'expires_at' => now()->addDays(5),
    ]);

    Passport::actingAs($admin);

    /** @var TestCase $this */
    $response = $this->getJson("/api/v1/admin/manufacturer/{$manufacturer->id}/additional-information");

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.message', 'Need more docs');
});

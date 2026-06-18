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

test('admin can request video uploads in additional information request', function () {
    Queue::fake();

    $admin = User::factory()->create(['role' => UserRole::ADMIN->value]);
    $manufacturer = User::factory()->create([
        'role' => UserRole::MANUFACTURER->value,
        'status' => UserStatus::PENDING,
        'manufacture_status' => UserManuFactureStatus::PENDING,
    ]);

    Passport::actingAs($admin);

    /** @var TestCase $this */
    $response = $this->postJson("/api/v1/admin/manufacturer/{$manufacturer->id}/additional-information", [
        'message' => 'Please upload a factory walkthrough video.',
        'allowed_types' => ['video'],
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.allowed_types', ['video'])
        ->assertJsonPath('data.allowed_type_labels', ['Video']);
});

test('manufacturer can submit video via public token using file field', function () {
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
        'token' => 'video-token-file',
        'message' => 'Send a factory video.',
        'allowed_types' => ['video'],
        'status' => 'pending',
        'expires_at' => now()->addDays(3),
    ]);

    $file = UploadedFile::fake()->create('factory-tour.mp4', 500, 'video/mp4');

    /** @var TestCase $this */
    $response = $this->post("/api/v1/manufacturer/additional-information/{$request->token}", [
        'responses' => [
            ['type' => 'video', 'file' => $file],
        ],
    ]);

    $response->assertOk()
        ->assertJsonPath('data.status', 'submitted')
        ->assertJsonPath('data.responses.0.type', 'video')
        ->assertJsonPath('data.responses.0.is_video', true);

    $videoResponse = $request->fresh('responses')->responses->first();

    expect($videoResponse?->file_path)->toStartWith('manufacturer/additional-information/videos/');
    Storage::disk('public')->assertExists($videoResponse->file_path);
});

test('manufacturer can submit video via dedicated video field', function () {
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
        'token' => 'video-token-dedicated',
        'message' => 'Send a factory video.',
        'allowed_types' => ['video'],
        'status' => 'pending',
        'expires_at' => now()->addDays(3),
    ]);

    $file = UploadedFile::fake()->create('walkthrough.webm', 500, 'video/webm');

    /** @var TestCase $this */
    $response = $this->post("/api/v1/manufacturer/additional-information/{$request->token}", [
        'responses' => [
            ['type' => 'video', 'video' => $file],
        ],
    ]);

    $response->assertOk()
        ->assertJsonPath('data.responses.0.video_url', fn ($url) => is_string($url) && $url !== '');
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

test('admin manufacturer show includes pending and submitted additional information requests', function () {
    $admin = User::factory()->create(['role' => UserRole::ADMIN->value]);
    $manufacturer = User::factory()->create(['role' => UserRole::MANUFACTURER->value]);

    ManufacturerAdditionalInformationRequest::query()->create([
        'user_id' => $manufacturer->id,
        'requested_by' => $admin->id,
        'token' => 'show-pending-token',
        'message' => 'Pending request',
        'allowed_types' => ['text'],
        'status' => 'pending',
        'expires_at' => now()->addDays(5),
    ]);

    ManufacturerAdditionalInformationRequest::query()->create([
        'user_id' => $manufacturer->id,
        'requested_by' => $admin->id,
        'token' => 'show-submitted-token',
        'message' => 'Submitted request',
        'allowed_types' => ['document'],
        'status' => 'submitted',
        'expires_at' => now()->addDays(5),
        'submitted_at' => now(),
    ]);

    ManufacturerAdditionalInformationRequest::query()->create([
        'user_id' => $manufacturer->id,
        'requested_by' => $admin->id,
        'token' => 'show-expired-token',
        'message' => 'Expired request',
        'allowed_types' => ['text'],
        'status' => 'expired',
        'expires_at' => now()->subDay(),
    ]);

    Passport::actingAs($admin);

    /** @var TestCase $this */
    $response = $this->getJson("/api/v1/admin/manufacturer/{$manufacturer->id}");

    $response->assertOk()
        ->assertJsonCount(2, 'data.additional_information_requests');

    $messages = collect($response->json('data.additional_information_requests'))->pluck('message')->all();
    expect($messages)->toContain('Pending request')
        ->toContain('Submitted request');
});

test('admin can list all additional information requests globally', function () {
    $admin = User::factory()->create(['role' => UserRole::ADMIN->value]);
    $manufacturer = User::factory()->create(['role' => UserRole::MANUFACTURER->value]);

    ManufacturerAdditionalInformationRequest::query()->create([
        'user_id' => $manufacturer->id,
        'requested_by' => $admin->id,
        'token' => 'global-token',
        'message' => 'Global list request',
        'allowed_types' => ['text'],
        'status' => 'pending',
        'expires_at' => now()->addDays(5),
    ]);

    Passport::actingAs($admin);

    /** @var TestCase $this */
    $response = $this->getJson('/api/v1/admin/manufacturer-additional-information?status=pending');

    $response->assertOk()
        ->assertJsonPath('data.0.message', 'Global list request')
        ->assertJsonPath('data.0.manufacturer.id', $manufacturer->id);
});

test('pending manufacturer can list and submit additional information requests', function () {
    Storage::fake('public');

    $admin = User::factory()->create(['role' => UserRole::ADMIN->value]);
    $manufacturer = User::factory()->manufacturer()->create([
        'manufacture_status' => UserManuFactureStatus::PENDING,
    ]);

    $request = ManufacturerAdditionalInformationRequest::query()->create([
        'user_id' => $manufacturer->id,
        'requested_by' => $admin->id,
        'token' => 'auth-submit-token',
        'message' => 'Please upload your license.',
        'allowed_types' => ['text', 'image'],
        'status' => 'pending',
        'expires_at' => now()->addDays(3),
    ]);

    Passport::actingAs($manufacturer);

    /** @var TestCase $this */
    $list = $this->getJson('/api/v1/manufacturer/additional-information-requests');
    $list->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.message', 'Please upload your license.');

    $file = UploadedFile::fake()->image('license.jpg');

    $submit = $this->post("/api/v1/manufacturer/additional-information-requests/{$request->id}/submit", [
        'responses' => [
            ['type' => 'text', 'message' => 'Updated license attached.'],
            ['type' => 'image', 'file' => $file],
        ],
    ]);

    $submit->assertOk()
        ->assertJsonPath('data.status', 'submitted');

    expect($request->fresh()->status->value)->toBe('submitted');
});

test('approved manufacturer can list and submit additional information requests', function () {
    Storage::fake('public');

    $admin = User::factory()->create(['role' => UserRole::ADMIN->value]);
    $manufacturer = User::factory()->manufacturerApproved()->create();

    $request = ManufacturerAdditionalInformationRequest::query()->create([
        'user_id' => $manufacturer->id,
        'requested_by' => $admin->id,
        'token' => 'auth-submit-token',
        'message' => 'Please upload your license.',
        'allowed_types' => ['text', 'image'],
        'status' => 'pending',
        'expires_at' => now()->addDays(3),
    ]);

    Passport::actingAs($manufacturer);

    /** @var TestCase $this */
    $list = $this->getJson('/api/v1/manufacturer/additional-information-requests');
    $list->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.message', 'Please upload your license.');

    $file = UploadedFile::fake()->image('license.jpg');

    $submit = $this->post("/api/v1/manufacturer/additional-information-requests/{$request->id}/submit", [
        'responses' => [
            ['type' => 'text', 'message' => 'Updated license attached.'],
            ['type' => 'image', 'file' => $file],
        ],
    ]);

    $submit->assertOk()
        ->assertJsonPath('data.status', 'submitted');

    expect($request->fresh()->status->value)->toBe('submitted');
});

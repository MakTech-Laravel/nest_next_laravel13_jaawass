<?php

use App\Enums\AdditionalInformationType;
use App\Enums\UserManuFactureStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Jobs\SendMailJob;
use App\Jobs\Support\SendSupportTicketInAppNotificationJob;
use App\Models\ManufacturerAdditionalInformationRequest;
use App\Models\Ticket;
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
    Queue::fake([SendMailJob::class, SendSupportTicketInAppNotificationJob::class]);

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

    $request = ManufacturerAdditionalInformationRequest::query()
        ->where('user_id', $manufacturer->id)
        ->first();

    expect($request?->ticket_id)->not->toBeNull();
    expect(Ticket::query()->whereKey($request->ticket_id)->exists())->toBeTrue();

    Queue::assertPushed(SendMailJob::class, function (SendMailJob $job) use ($manufacturer): bool {
        return $job->recipient === $manufacturer->email
            && $job->template === 'manufacturer-additional-information';
    });
});

test('manufacturer can submit additional information via public token', function () {
    Queue::fake();
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

    Queue::assertPushed(SendMailJob::class, function (SendMailJob $job) use ($admin): bool {
        return $job->recipient === $admin->email
            && $job->template === 'admin-manufacturer-additional-information-response';
    });

    Queue::assertPushed(SendMailJob::class, function (SendMailJob $job) use ($manufacturer): bool {
        return $job->recipient === $manufacturer->email
            && $job->template === 'manufacturer-additional-information-received';
    });

    Queue::assertPushed(SendSupportTicketInAppNotificationJob::class, function (SendSupportTicketInAppNotificationJob $job) use ($admin, $manufacturer): bool {
        return $job->recipientId === $admin->id
            && $job->senderId === $manufacturer->id
            && $job->type === 'manufacturer.additional_information.submitted';
    });
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
    Queue::fake();
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
    Queue::fake();
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

test('manufacturer me and profile include verification and all additional information requests', function () {
    config(['app.frontend_url' => 'https://app.example.com']);

    $admin = User::factory()->create(['role' => UserRole::ADMIN->value]);
    $manufacturer = User::factory()->create([
        'role' => UserRole::MANUFACTURER->value,
        'status' => UserStatus::PENDING,
        'manufacture_status' => UserManuFactureStatus::PENDING,
        'manufacture_status_at' => now()->subDay(),
    ]);

    ManufacturerAdditionalInformationRequest::query()->create([
        'user_id' => $manufacturer->id,
        'requested_by' => $admin->id,
        'token' => 'me-endpoint-token',
        'message' => 'Pending verification docs',
        'allowed_types' => ['document'],
        'status' => 'pending',
        'expires_at' => now()->addDays(5),
    ]);

    ManufacturerAdditionalInformationRequest::query()->create([
        'user_id' => $manufacturer->id,
        'requested_by' => $admin->id,
        'token' => 'me-submitted-token',
        'message' => 'Submitted verification docs',
        'allowed_types' => ['text'],
        'status' => 'submitted',
        'expires_at' => now()->addDays(5),
        'submitted_at' => now(),
    ]);

    Passport::actingAs($manufacturer);

    /** @var TestCase $this */
    $meResponse = $this->getJson('/api/v1/me');

    $meResponse->assertOk()
        ->assertJsonPath('data.verification.manufacture_status', UserManuFactureStatus::PENDING->value)
        ->assertJsonPath('data.verification.rejection_reason', null)
        ->assertJsonCount(2, 'data.additional_information_requests');

    $pending = collect($meResponse->json('data.additional_information_requests'))
        ->firstWhere('message', 'Pending verification docs');

    expect($pending)->not->toBeNull()
        ->and($pending['submit_url'])->toBe(
            'https://app.example.com/review?token=me-endpoint-token'
        );

    $subscribedManufacturer = manufacturerWithSubscription([
        'manufacture_status' => UserManuFactureStatus::PENDING->value,
        'manufacture_status_at' => now()->subDay(),
    ]);

    ManufacturerAdditionalInformationRequest::query()->create([
        'user_id' => $subscribedManufacturer->id,
        'requested_by' => $admin->id,
        'token' => 'profile-endpoint-token',
        'message' => 'Profile pending docs',
        'allowed_types' => ['document'],
        'status' => 'pending',
        'expires_at' => now()->addDays(5),
    ]);

    Passport::actingAs($subscribedManufacturer);

    $this->getJson('/api/v1/manufacturer/profile')
        ->assertOk()
        ->assertJsonPath('data.verification.manufacture_status', UserManuFactureStatus::PENDING->value)
        ->assertJsonPath('data.additional_information_requests.0.message', 'Profile pending docs');
});

test('admin can list all open additional information requests for review management', function () {
    $admin = User::factory()->create(['role' => UserRole::ADMIN->value]);
    $pendingManufacturer = User::factory()->create([
        'role' => UserRole::MANUFACTURER->value,
        'manufacture_status' => UserManuFactureStatus::PENDING,
    ]);
    $approvedManufacturer = User::factory()->create([
        'role' => UserRole::MANUFACTURER->value,
        'manufacture_status' => UserManuFactureStatus::APPROVED,
    ]);

    ManufacturerAdditionalInformationRequest::query()->create([
        'user_id' => $pendingManufacturer->id,
        'requested_by' => $admin->id,
        'token' => 'pending-open-token',
        'message' => 'Awaiting manufacturer response',
        'allowed_types' => ['document'],
        'status' => 'pending',
        'expires_at' => now()->addDays(5),
    ]);

    ManufacturerAdditionalInformationRequest::query()->create([
        'user_id' => $pendingManufacturer->id,
        'requested_by' => $admin->id,
        'token' => 'submitted-open-token',
        'message' => 'Submitted for admin review',
        'allowed_types' => ['text'],
        'status' => 'submitted',
        'expires_at' => now()->addDays(5),
        'submitted_at' => now(),
    ]);

    ManufacturerAdditionalInformationRequest::query()->create([
        'user_id' => $approvedManufacturer->id,
        'requested_by' => $admin->id,
        'token' => 'approved-manufacturer-token',
        'message' => 'Should be hidden by default',
        'allowed_types' => ['text'],
        'status' => 'submitted',
        'expires_at' => now()->addDays(5),
        'submitted_at' => now(),
    ]);

    Passport::actingAs($admin);

    /** @var TestCase $this */
    $response = $this->getJson('/api/v1/admin/manufacturer-additional-information?per_page=10');

    $response->assertOk()
        ->assertJsonPath('meta.total', 3)
        ->assertJsonCount(3, 'data');

    $messages = collect($response->json('data'))->pluck('message')->all();

    expect($messages)->toContain('Awaiting manufacturer response')
        ->toContain('Submitted for admin review')
        ->toContain('Should be hidden by default');

    $this->getJson('/api/v1/admin/manufacturer-additional-information?status=submitted')
        ->assertOk()
        ->assertJsonPath('meta.total', 2);

    $messagesForSubmitted = collect(
        $this->getJson('/api/v1/admin/manufacturer-additional-information?status=submitted')->json('data')
    )->pluck('message')->all();

    expect($messagesForSubmitted)->toContain('Should be hidden by default')
        ->toContain('Submitted for admin review');

    $this->getJson('/api/v1/admin/manufacturer-additional-information?unverified_only=1')
        ->assertOk()
        ->assertJsonPath('meta.total', 2);

    $this->getJson('/api/v1/admin/manufacturer-additional-information?search='.$approvedManufacturer->email)
        ->assertOk()
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.message', 'Should be hidden by default');
});

test('admin can filter accepted additional information requests', function () {
    $admin = User::factory()->create(['role' => UserRole::ADMIN->value]);
    $manufacturer = User::factory()->create([
        'role' => UserRole::MANUFACTURER->value,
        'manufacture_status' => UserManuFactureStatus::APPROVED,
    ]);

    ManufacturerAdditionalInformationRequest::query()->create([
        'user_id' => $manufacturer->id,
        'requested_by' => $admin->id,
        'token' => 'accepted-filter-token',
        'message' => 'Accepted request should appear in filter',
        'allowed_types' => ['text'],
        'status' => 'accepted',
        'expires_at' => now()->addDays(5),
        'submitted_at' => now()->subDay(),
        'reviewed_at' => now(),
        'reviewed_by' => $admin->id,
    ]);

    ManufacturerAdditionalInformationRequest::query()->create([
        'user_id' => $manufacturer->id,
        'requested_by' => $admin->id,
        'token' => 'pending-approved-manufacturer-token',
        'message' => 'Pending request for approved manufacturer',
        'allowed_types' => ['document'],
        'status' => 'pending',
        'expires_at' => now()->addDays(5),
    ]);

    Passport::actingAs($admin);

    /** @var TestCase $this */
    $this->getJson('/api/v1/admin/manufacturer-additional-information?status=accepted')
        ->assertOk()
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.status', 'accepted');

    $this->getJson('/api/v1/admin/manufacturer-additional-information?status=pending')
        ->assertOk()
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.message', 'Pending request for approved manufacturer');
});

test('admin can accept a submitted additional information request', function () {
    $admin = User::factory()->create(['role' => UserRole::ADMIN->value]);
    $manufacturer = User::factory()->create([
        'role' => UserRole::MANUFACTURER->value,
        'manufacture_status' => UserManuFactureStatus::PENDING,
    ]);

    $ticket = Ticket::query()->create([
        'user_id' => $manufacturer->id,
        'subject' => 'Additional information review',
        'department_type' => 'account',
        'priority' => 'medium',
        'status' => 'waiting_on_customer',
        'assigned_to' => $admin->id,
    ]);

    $informationRequest = ManufacturerAdditionalInformationRequest::query()->create([
        'user_id' => $manufacturer->id,
        'requested_by' => $admin->id,
        'ticket_id' => $ticket->id,
        'token' => 'accept-review-token',
        'message' => 'Please submit your license.',
        'allowed_types' => ['document'],
        'status' => 'submitted',
        'expires_at' => now()->addDays(5),
        'submitted_at' => now(),
    ]);

    Passport::actingAs($admin);

    /** @var TestCase $this */
    $this->patchJson("/api/v1/admin/manufacturer-additional-information/{$informationRequest->id}/review", [
        'action' => 'accept',
        'notes' => 'Documents look good.',
    ])
        ->assertOk()
        ->assertJsonPath('data.status', 'accepted');

    $informationRequest->refresh();
    $manufacturer->refresh();
    $ticket->refresh();

    expect($informationRequest->status->value)->toBe('accepted')
        ->and($informationRequest->review_notes)->toBe('Documents look good.')
        ->and($manufacturer->manufacture_status)->toBe(UserManuFactureStatus::APPROVED)
        ->and($ticket->status->value)->toBe('resolved');
});

test('admin can reject a submitted additional information request', function () {
    $admin = User::factory()->create(['role' => UserRole::ADMIN->value]);
    $manufacturer = User::factory()->create([
        'role' => UserRole::MANUFACTURER->value,
        'manufacture_status' => UserManuFactureStatus::PENDING,
    ]);

    $informationRequest = ManufacturerAdditionalInformationRequest::query()->create([
        'user_id' => $manufacturer->id,
        'requested_by' => $admin->id,
        'token' => 'reject-review-token',
        'message' => 'Please submit your license.',
        'allowed_types' => ['document'],
        'status' => 'submitted',
        'expires_at' => now()->addDays(5),
        'submitted_at' => now(),
    ]);

    Passport::actingAs($admin);

    /** @var TestCase $this */
    $this->patchJson("/api/v1/admin/manufacturer-additional-information/{$informationRequest->id}/review", [
        'action' => 'reject',
        'reason' => 'Documents are incomplete.',
    ])
        ->assertOk()
        ->assertJsonPath('data.status', 'rejected');

    $informationRequest->refresh();
    $manufacturer->refresh();

    expect($informationRequest->status->value)->toBe('rejected')
        ->and($informationRequest->review_notes)->toBe('Documents are incomplete.')
        ->and($manufacturer->manufacture_status)->toBe(UserManuFactureStatus::REJECTED)
        ->and($manufacturer->manufacture_status_reason)->toBe('Documents are incomplete.');
});

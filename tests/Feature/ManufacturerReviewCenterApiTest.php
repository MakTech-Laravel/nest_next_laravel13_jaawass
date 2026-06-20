<?php

use App\Enums\AdditionalInformationRequestStatus;
use App\Enums\UserManuFactureStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Company;
use App\Models\ManufacturerAdditionalInformationRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

test('manufacturer can fetch review center with verification and additional information', function () {
    $admin = User::factory()->create(['role' => UserRole::ADMIN->value]);
    $manufacturer = User::factory()->create([
        'role' => UserRole::MANUFACTURER->value,
        'status' => UserStatus::ACTIVE,
        'manufacture_status' => UserManuFactureStatus::APPROVED,
        'manufacture_status_at' => now()->subDay(),
    ]);

    Company::query()->create([
        'user_id' => $manufacturer->id,
        'company_name' => 'Acme Manufacturing',
    ]);

    $pendingRequest = ManufacturerAdditionalInformationRequest::query()->create([
        'user_id' => $manufacturer->id,
        'requested_by' => $admin->id,
        'token' => 'review-center-token',
        'message' => 'Please upload your factory license.',
        'allowed_types' => ['document', 'text'],
        'status' => AdditionalInformationRequestStatus::Pending,
        'expires_at' => now()->addDays(3),
    ]);

    ManufacturerAdditionalInformationRequest::query()->create([
        'user_id' => $manufacturer->id,
        'requested_by' => $admin->id,
        'token' => 'submitted-token',
        'message' => 'Provide updated photos.',
        'allowed_types' => ['image'],
        'status' => AdditionalInformationRequestStatus::Submitted,
        'expires_at' => now()->addDays(3),
        'submitted_at' => now(),
    ]);

    Passport::actingAs($manufacturer);

    /** @var TestCase $this */
    $response = $this->getJson('/api/v1/manufacturer/review-center');

    $response->assertOk()
        ->assertJsonPath('data.user.id', $manufacturer->id)
        ->assertJsonPath('data.user.company_name', 'Acme Manufacturing')
        ->assertJsonPath('data.verification.manufacture_status', UserManuFactureStatus::APPROVED->value)
        ->assertJsonPath('data.verification.manufacture_status_label', 'Approved')
        ->assertJsonPath('data.verification.rejection_reason', null)
        ->assertJsonCount(2, 'data.additional_information_requests')
        ->assertJsonPath('data.review_requests', []);

    $requests = collect($response->json('data.additional_information_requests'));
    $pending = $requests->firstWhere('id', $pendingRequest->id);

    expect($pending)->not->toBeNull()
        ->and($pending['status'])->toBe('pending')
        ->and($pending['submit_url'])->toContain('/manufacturer-additional-information-request/review-center-token');
});

test('review center returns rejection reason for rejected manufacturers', function () {
    $manufacturer = User::factory()->create([
        'role' => UserRole::MANUFACTURER->value,
        'status' => UserStatus::ACTIVE,
        'manufacture_status' => UserManuFactureStatus::REJECTED,
        'manufacture_status_reason' => 'Incomplete documentation',
        'manufacture_status_at' => now(),
    ]);

    Passport::actingAs($manufacturer);

    /** @var TestCase $this */
    $this->getJson('/api/v1/manufacturer/review-center')
        ->assertOk()
        ->assertJsonPath('data.verification.manufacture_status', UserManuFactureStatus::REJECTED->value)
        ->assertJsonPath('data.verification.rejection_reason', 'Incomplete documentation');
});

test('review center requires manufacturer authentication', function () {
    /** @var TestCase $this */
    $this->getJson('/api/v1/manufacturer/review-center')
        ->assertUnauthorized();
});

test('buyer cannot access manufacturer review center', function () {
    $buyer = User::factory()->create(['role' => UserRole::BUYER->value]);

    Passport::actingAs($buyer);

    /** @var TestCase $this */
    $this->getJson('/api/v1/manufacturer/review-center')
        ->assertForbidden();
});

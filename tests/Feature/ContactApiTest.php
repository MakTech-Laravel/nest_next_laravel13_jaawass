<?php

use App\Enums\UserRole;
use App\Models\Contact;
use App\Models\ContactTranslation;
use App\Models\Language;
use App\Models\User;
use App\Services\Translation\TranslationOrchestrator;
use Database\Seeders\LanguageSeeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\ClientRepository;

uses(RefreshDatabase::class);

beforeEach(function () {
    app(ClientRepository::class)->createPersonalAccessGrantClient(
        name: 'Test Personal Access Client',
        provider: config('auth.guards.api.provider')
    );
});

function fakeContactTranslationOrchestrator(): void
{
    config()->set('translation.queue.enabled', false);
    config()->set('translation.cache.enabled', false);

    app()->instance(TranslationOrchestrator::class, new class
    {
        public function handle(Model $model, array $sourceData, ?string $sourceLocale = null): void
        {
            $translatableFields = $model->translatableFields();
            $sourceData = array_intersect_key($sourceData, array_flip($translatableFields));

            if (empty($sourceData)) {
                return;
            }

            $resolvedSource = $sourceLocale ?? config('translation.source_locale', 'en');
            $targets = Language::translationTargets($resolvedSource);

            $translatedByLocale = [];

            foreach ($targets as $language) {
                $translated = [];

                foreach ($sourceData as $fieldKey => $_sourceText) {
                    $translated[$fieldKey] = $language->locale.'_'.$fieldKey.'_t';
                }

                $translatedByLocale[$language->locale] = $translated;
            }

            $translatedByLocale[$resolvedSource] = $sourceData;

            $model->upsertTranslations($translatedByLocale);
        }
    });
}

test('public contact store creates contact with translations', function () {
    $this->seed(LanguageSeeder::class);
    Language::clearCache();
    fakeContactTranslationOrchestrator();

    $response = $this->postJson('/api/v1/contact', [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'company_name' => 'Acme Corp',
        'inquiry_type' => 'sales',
        'message' => 'I would like more information about your products.',
        'locale' => 'en',
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.name', 'Jane Doe')
        ->assertJsonPath('data.email', 'jane@example.com')
        ->assertJsonPath('data.company_name', 'Acme Corp')
        ->assertJsonPath('data.inquiry_type', 'sales')
        ->assertJsonPath('data.message', 'I would like more information about your products.')
        ->assertJsonPath('data.is_read', false);

    $contact = Contact::query()->first();

    expect($contact)->not->toBeNull();
    expect(ContactTranslation::query()->where('contact_id', $contact->id)->where('locale', 'en')->exists())->toBeTrue();
});

test('admin can list contacts with filters', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN->value,
    ]);
    $token = $admin->createToken('test')->accessToken;

    Contact::factory()->create([
        'name' => 'Unread Contact',
        'email' => 'unread@example.com',
        'is_read' => false,
    ]);

    Contact::factory()->read()->create([
        'name' => 'Read Contact',
        'email' => 'read@example.com',
    ]);

    $this->withToken($token)
        ->getJson('/api/v1/admin/contacts?is_read=0&search=Unread')
        ->assertSuccessful()
        ->assertJsonPath('success', true)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Unread Contact');
});

test('admin can show update read status and delete contact', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN->value,
    ]);
    $token = $admin->createToken('test')->accessToken;

    $contact = Contact::factory()->create([
        'name' => 'Support Inquiry',
        'email' => 'support@example.com',
        'message' => 'Need help with my order.',
        'is_read' => false,
    ]);

    ContactTranslation::create([
        'contact_id' => $contact->id,
        'locale' => 'en',
        'message' => 'Need help with my order.',
    ]);

    $this->withToken($token)
        ->getJson("/api/v1/admin/contacts/{$contact->id}")
        ->assertSuccessful()
        ->assertJsonPath('data.id', $contact->id)
        ->assertJsonPath('data.message', 'Need help with my order.');

    $this->withToken($token)
        ->patchJson("/api/v1/admin/contacts/{$contact->id}/read-status", [
            'is_read' => true,
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.is_read', true);

    expect($contact->fresh()->is_read)->toBeTrue();

    $this->withToken($token)
        ->deleteJson("/api/v1/admin/contacts/{$contact->id}")
        ->assertSuccessful()
        ->assertJsonPath('success', true);

    expect(Contact::query()->whereKey($contact->id)->exists())->toBeFalse();
    expect(ContactTranslation::query()->where('contact_id', $contact->id)->exists())->toBeFalse();
});

test('admin show returns translated not found for missing contact', function () {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN->value,
    ]);
    $token = $admin->createToken('test')->accessToken;

    $this->withToken($token)
        ->getJson('/api/v1/admin/contacts/99999')
        ->assertNotFound()
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', __('common.not_found'))
        ->assertJsonPath('data', null);
});

test('admin contact routes require authentication', function () {
    $contact = Contact::factory()->create();

    $this->getJson('/api/v1/admin/contacts')->assertUnauthorized();
    $this->getJson("/api/v1/admin/contacts/{$contact->id}")->assertUnauthorized();
    $this->patchJson("/api/v1/admin/contacts/{$contact->id}/read-status", ['is_read' => true])->assertUnauthorized();
    $this->deleteJson("/api/v1/admin/contacts/{$contact->id}")->assertUnauthorized();
});

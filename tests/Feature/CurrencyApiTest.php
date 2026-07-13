<?php

use App\Enums\CurrencyRateSource;
use App\Enums\UserRole;
use App\Models\Currency;
use App\Models\CurrencyExchangeRate;
use App\Models\Product;
use App\Models\User;
use App\Services\Currency\CurrencyDisplayResolver;
use App\Services\Currency\ExchangeRateService;
use Carbon\CarbonImmutable;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;

uses(RefreshDatabase::class);

beforeEach(function () {
    app(ClientRepository::class)->createPersonalAccessGrantClient(
        name: 'Test Personal Access Client',
        provider: config('auth.guards.api.provider')
    );

    $this->seed(CurrencySeeder::class);
});

test('public currencies index returns allowlisted active currencies', function () {
    $response = $this->getJson('/api/v1/currencies');

    $response->assertOk()
        ->assertJsonPath('data.0.code', 'USD')
        ->assertJsonPath('data.1.code', 'EUR')
        ->assertJsonPath('data.2.code', 'SAR');
});

test('product price payload uses structured money and conversion flag without rates', function () {
    $product = Product::factory()->create([
        'price' => 100,
    ]);

    $response = $this->getJson('/api/v1/products');

    $response->assertOk()
        ->assertJsonPath('data.0.price.amount', '100.00')
        ->assertJsonPath('data.0.price.currency', 'USD')
        ->assertJsonPath('data.0.price_display', null)
        ->assertJsonPath('data.0.conversion_available', true);
});

test('get listing prefers X-App-Currency over authenticated user preferred currency', function () {
    $usd = Currency::query()->where('code', 'USD')->firstOrFail();
    $eur = Currency::query()->where('code', 'EUR')->firstOrFail();

    $rate = new CurrencyExchangeRate;
    $rate->base_currency_id = $usd->id;
    $rate->quote_currency_id = $eur->id;
    $rate->rate = '0.92';
    $rate->effective_at = now()->subDay();
    $rate->source = CurrencyRateSource::Api;
    $rate->sync_batch_id = null;
    $rate->created_by_user_id = null;
    $rate->save();

    $user = User::factory()->create(['preferred_currency_id' => $eur->id]);
    Passport::actingAs($user);

    Product::factory()->create([
        'price' => 100,
        'currency_id' => $usd->id,
    ]);

    $asUserPreferred = $this->getJson('/api/v1/products');
    $asUserPreferred->assertOk()
        ->assertJsonPath('data.0.price_display.currency', 'EUR');

    // Scoped services (e.g. currency context) persist on the container between `getJson` calls in one test.
    app()->forgetScopedInstances();

    $withHeaderUsd = $this->getJson('/api/v1/products', [
        'X-App-Currency' => 'USD',
    ]);
    $withHeaderUsd->assertOk()
        ->assertJsonPath('data.0.price_display', null);
});

test('display currency header requests conversion when rate exists', function () {
    $usd = Currency::query()->where('code', 'USD')->firstOrFail();
    $eur = Currency::query()->where('code', 'EUR')->firstOrFail();

    $rate = new CurrencyExchangeRate;
    $rate->base_currency_id = $usd->id;
    $rate->quote_currency_id = $eur->id;
    $rate->rate = '0.92';
    $rate->effective_at = now()->subDay();
    $rate->source = CurrencyRateSource::Api;
    $rate->sync_batch_id = null;
    $rate->created_by_user_id = null;
    $rate->save();

    $product = Product::factory()->create([
        'price' => 100,
        'currency_id' => $usd->id,
    ]);

    $response = $this->getJson('/api/v1/products', [
        'X-App-Currency' => 'EUR',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.0.price.currency', 'USD')
        ->assertJsonPath('data.0.conversion_available', true)
        ->assertJsonPath('data.0.price_display.currency', 'EUR');
});

test('authenticated user can set currency preference', function () {
    $user = User::factory()->create();
    Passport::actingAs($user);

    $response = $this->patchJson('/api/v1/me/preferences', [
        'currency_code' => 'eur',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.preferred_currency.code', 'EUR');

    expect($user->fresh()->preferred_currency_id)->toBe(
        Currency::query()->where('code', 'EUR')->value('id')
    );
});

test('authenticated user can set language preference via me/preferences', function () {
    $user = User::factory()->create(['preferred_language' => 'en']);
    Passport::actingAs($user);

    $response = $this->patchJson('/api/v1/me/preferences', [
        'preferred_language' => 'zh_CN',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.preferred_language', 'zh_CN');

    expect($user->fresh()->preferred_language)->toBe('zh_CN');
});

test('legacy preferred_language es is normalized to zh_CN', function () {
    $user = User::factory()->create(['preferred_language' => 'en']);
    Passport::actingAs($user);

    $response = $this->patchJson('/api/v1/me/preferences', [
        'preferred_language' => 'es',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.preferred_language', 'zh_CN');

    expect($user->fresh()->preferred_language)->toBe('zh_CN');
});

test('display currency resolver orders header before user on GET', function () {
    $eur = Currency::query()->where('code', 'EUR')->firstOrFail();
    $user = User::factory()->create(['preferred_currency_id' => $eur->id]);

    $request = Request::create('/api/v1/products', 'GET', [], [], [], [
        'HTTP_X_APP_CURRENCY' => 'USD',
    ]);
    $request->setUserResolver(fn () => $user);

    $resolver = new CurrencyDisplayResolver($request);

    expect($resolver->resolve()->code)->toBe('USD');
});

test('display currency resolver accepts Accept-Currency on GET', function () {
    $eur = Currency::query()->where('code', 'EUR')->firstOrFail();
    $user = User::factory()->create(['preferred_currency_id' => $eur->id]);

    $request = Request::create('/api/v1/products', 'GET', [], [], [], [
        'HTTP_ACCEPT_CURRENCY' => 'USD, EUR;q=0.9',
    ]);
    $request->setUserResolver(fn () => $user);

    $resolver = new CurrencyDisplayResolver($request);

    expect($resolver->resolve()->code)->toBe('USD');
});

test('display currency resolver orders user preference before header on POST', function () {
    $eur = Currency::query()->where('code', 'EUR')->firstOrFail();
    $user = User::factory()->create(['preferred_currency_id' => $eur->id]);

    $request = Request::create('/api/v1/checkout', 'POST', [], [], [], [
        'HTTP_X_APP_CURRENCY' => 'USD',
    ]);
    $request->setUserResolver(fn () => $user);

    $resolver = new CurrencyDisplayResolver($request);

    expect($resolver->resolve()->code)->toBe('EUR');
});

test('login response includes preferred currency and language', function () {
    $eur = Currency::query()->where('code', 'EUR')->firstOrFail();
    User::factory()->create([
        'email' => 'prefs@example.com',
        'password' => 'password',
        'role' => UserRole::BUYER->value,
        'preferred_currency_id' => $eur->id,
        'preferred_language' => 'zh_CN',
    ]);

    $response = $this->postJson('/api/v1/login', [
        'email' => 'prefs@example.com',
        'password' => 'password',
        'role' => UserRole::BUYER->value,
    ]);

    $response->assertOk()
        ->assertJsonPath('data.user.preferred_language', 'zh_CN')
        ->assertJsonPath('data.user.preferred_currency.code', 'EUR');
});

test('admin can append manual exchange rate', function () {
    $admin = User::factory()->admin()->create();
    Passport::actingAs($admin);

    $response = $this->postJson('/api/v1/admin/currency/rates', [
        'quote_currency_code' => 'EUR',
        'rate' => 0.95,
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.quote', 'EUR')
        ->assertJsonPath('data.source', 'manual');

    expect(CurrencyExchangeRate::query()->count())->toBe(1);
});

test('exchange rate service rateAt selects latest row before moment', function () {
    $usd = Currency::query()->where('code', 'USD')->firstOrFail();
    $eur = Currency::query()->where('code', 'EUR')->firstOrFail();

    foreach (['0.80', '0.90'] as $i => $r) {
        $row = new CurrencyExchangeRate;
        $row->base_currency_id = $usd->id;
        $row->quote_currency_id = $eur->id;
        $row->rate = $r;
        $row->effective_at = CarbonImmutable::parse('2020-01-0'.(1 + $i), 'UTC');
        $row->source = CurrencyRateSource::Manual;
        $row->save();
    }

    $svc = app(ExchangeRateService::class);
    $picked = $svc->rateAt($usd, $eur, CarbonImmutable::parse('2020-01-15', 'UTC'));

    expect($picked)->not->toBeNull()
        ->and((float) (string) $picked->rate)->toBe(0.9);
});

test('currency sync command inserts api rows when http fake succeeds', function () {
    config(['currency.fx_sync.enabled' => true]);

    Http::fake([
        'api.frankfurter.app/*' => Http::response([
            'amount' => 1,
            'base' => 'USD',
            'date' => '2024-06-01',
            'rates' => [
                'EUR' => 0.93,
                'SAR' => 3.75,
            ],
        ], 200),
    ]);

    $this->artisan('currency:sync-rates')->assertSuccessful();

    expect(CurrencyExchangeRate::query()->where('source', CurrencyRateSource::Api)->count())->toBe(2);
});

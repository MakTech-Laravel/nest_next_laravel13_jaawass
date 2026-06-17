<?php

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('manufacturer company gets unique slug on create', function (): void {
    $manufacturer = User::factory()->manufacturer()->create([
        'first_name' => 'Acme',
        'last_name' => 'Corp',
    ]);

    $company = Company::query()->create([
        'user_id' => $manufacturer->id,
        'company_name' => 'Acme Manufacturing',
        'country' => 'China',
    ]);

    expect($company->slug)->toBe('acme-manufacturing');
});

test('duplicate company names receive unique slugs', function (): void {
    $firstManufacturer = User::factory()->manufacturer()->create();
    $secondManufacturer = User::factory()->manufacturer()->create();

    $firstCompany = Company::query()->create([
        'user_id' => $firstManufacturer->id,
        'company_name' => 'Global Textiles',
        'country' => 'China',
    ]);

    $secondCompany = Company::query()->create([
        'user_id' => $secondManufacturer->id,
        'company_name' => 'Global Textiles',
        'country' => 'India',
    ]);

    expect($firstCompany->slug)->toBe('global-textiles')
        ->and($secondCompany->slug)->toBe('global-textiles-2');
});

test('company slug updates when company name changes', function (): void {
    $manufacturer = User::factory()->manufacturer()->create();

    $company = Company::query()->create([
        'user_id' => $manufacturer->id,
        'company_name' => 'Old Name Ltd',
        'country' => 'China',
    ]);

    $company->update(['company_name' => 'New Name Ltd']);

    expect($company->fresh()->slug)->toBe('new-name-ltd');
});

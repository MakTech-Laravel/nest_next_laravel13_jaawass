<?php

declare(strict_types=1);

use App\Enums\QuickFilterType;
use App\Models\QuickFilterOption;
use App\Services\QuickFilterService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('quick filter counts and crud with sort', function (): void {
    $svc = app(QuickFilterService::class);

    expect($svc->getCounts())->toMatchArray([
        'filter_types' => 4,
        'total_options' => 0,
        'enabled' => 0,
        'disabled' => 0,
    ]);

    $a = $svc->create(QuickFilterType::Countries, 'South Korea', null, true);
    $b = $svc->create(QuickFilterType::Countries, 'Japan', null, true);

    expect($a->value)->toBe('south-korea')
        ->and($b->value)->toBe('japan')
        ->and($svc->listByType(QuickFilterType::Countries))->toHaveCount(2);

    $counts = $svc->getCounts();
    expect($counts['total_options'])->toBe(2)->and($counts['enabled'])->toBe(2);

    $svc->toggle($a, false);
    expect($a->fresh()->is_enabled)->toBeFalse();
    expect($svc->getCounts()['disabled'])->toBe(1);

    $svc->update($b, ['display_label' => 'Japan Updated']);
    expect($b->fresh()->display_label)->toBe('Japan Updated');

    $svc->moveSort($b->fresh(), 'up');
    $ordered = $svc->listByType(QuickFilterType::Countries)->pluck('id')->all();
    expect($ordered[0])->toBe($b->id);

    $svc->delete($a->fresh());
    expect(QuickFilterOption::query()->count())->toBe(1);
});

test('list enabled by type excludes disabled', function (): void {
    $svc = app(QuickFilterService::class);
    $svc->create(QuickFilterType::ExportMarkets, 'Europe', 'europe', true);
    $x = $svc->create(QuickFilterType::ExportMarkets, 'Hidden', 'hidden', true);
    $svc->toggle($x, false);

    expect($svc->listEnabledByType(QuickFilterType::ExportMarkets))->toHaveCount(1);
});

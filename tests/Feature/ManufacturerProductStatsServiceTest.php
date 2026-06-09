<?php

declare(strict_types=1);

use App\Enums\Api\V1\ProductStatusEnum;
use App\Models\Conversation;
use App\Models\Currency;
use App\Models\Industry;
use App\Models\Product;
use App\Models\SubCategory;
use App\Models\User;
use App\Services\Manufacturer\ManufacturerProductStatsService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('manufacturer product stats aggregates products and buyer-started conversations', function (): void {
    $currency = Currency::query()->where('code', 'USD')->firstOrFail();

    $industry = Industry::query()->create([
        'name' => 'Industry',
        'description' => null,
        'slug' => 'industry-'.uniqid(),
        'icon' => null,
        'color' => null,
        'featured' => false,
        'sort_order' => 0,
    ]);

    $subCategory = SubCategory::query()->create([
        'industry_id' => $industry->id,
        'name' => 'Sub',
        'slug' => 'sub-'.uniqid(),
        'icon' => null,
        'sort_order' => 0,
    ]);

    $manufacturer = User::factory()->manufacturerApproved()->create();
    $buyer = User::factory()->create();

    $baseProduct = [
        'user_id' => $manufacturer->id,
        'name' => 'Widget',
        'description' => 'Desc',
        'slug' => 'widget-'.uniqid(),
        'industry_id' => $industry->id,
        'sub_category_id' => $subCategory->id,
        'currency_id' => $currency->id,
        'keywords' => null,
        'status' => ProductStatusEnum::ACTIVE->value,
    ];

    $p1 = Product::query()->create([...$baseProduct, 'slug' => 'w-a-'.uniqid()]);
    $p1->forceFill(['view_count' => 100])->save();
    $p2 = Product::query()->create([...$baseProduct, 'slug' => 'w-b-'.uniqid(), 'status' => ProductStatusEnum::DRAFT->value]);
    $p2->forceFill(['view_count' => 200])->save();

    $otherManufacturer = User::factory()->manufacturerApproved()->create();
    $pOther = Product::query()->create([
        'user_id' => $otherManufacturer->id,
        'name' => 'Other',
        'description' => 'x',
        'slug' => 'other-'.uniqid(),
        'industry_id' => $industry->id,
        'sub_category_id' => $subCategory->id,
        'currency_id' => $currency->id,
        'keywords' => null,
        'status' => ProductStatusEnum::ACTIVE->value,
    ]);
    $pOther->forceFill(['view_count' => 9999])->save();

    $buyerStarted = Conversation::factory()->create(['created_by' => $buyer->id]);
    $buyerStarted->participants()->attach([$buyer->id, $manufacturer->id]);

    $mfgStarted = Conversation::factory()->create(['created_by' => $manufacturer->id]);
    $mfgStarted->participants()->attach([$buyer->id, $manufacturer->id]);

    $data = app(ManufacturerProductStatsService::class)->getStats($manufacturer);

    expect($data['total_products'])->toBe(2)
        ->and($data['active_products'])->toBe(1)
        ->and($data['total_views'])->toBe(300)
        ->and($data['total_inquiries'])->toBe(1);
});

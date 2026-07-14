<?php

declare(strict_types=1);

use App\Jobs\Order\SendOrderInAppNotificationJob;
use App\Jobs\SendMailJob;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    app(ClientRepository::class)->createPersonalAccessGrantClient(
        name: 'Test Personal Access Client',
        provider: config('auth.guards.api.provider')
    );

    Queue::fake([SendMailJob::class, SendOrderInAppNotificationJob::class]);
    config([
        'app.frontend_url' => 'http://localhost:3000',
        'broadcasting.default' => 'null',
    ]);
});

test('order created queues emails for buyer manufacturer and admin with shared template roles', function (): void {
    ['buyer' => $buyer, 'manufacturer' => $manufacturer, 'product' => $product] = seedManufacturerOrderScenario();
    $admin = User::factory()->admin()->create();

    Passport::actingAs($manufacturer);

    $response = $this->postJson('/api/v1/manufacturer/orders/create', buildManufacturerOrderCreatePayload(
        buyerId: $buyer->id,
        items: [[
            'product_id' => $product->id,
            'quantity' => 100,
            'unit_price' => 10,
        ]],
    ));

    $response->assertCreated();
    $orderId = (int) $response->json('data.id');

    Queue::assertPushed(SendMailJob::class, function (SendMailJob $job) use ($buyer, $orderId): bool {
        return $job->recipient === $buyer->email
            && $job->template === 'manufacturer-order-created'
            && ($job->data['recipientRole'] ?? null) === 'buyer'
            && ($job->data['ctaUrl'] ?? null) === "http://localhost:3000/dashboard/buyer/orders/{$orderId}";
    });

    Queue::assertPushed(SendMailJob::class, function (SendMailJob $job) use ($manufacturer, $orderId): bool {
        return $job->recipient === $manufacturer->email
            && $job->template === 'order-created-manufacturer'
            && ($job->data['recipientRole'] ?? null) === 'manufacturer'
            && ($job->data['ctaUrl'] ?? null) === "http://localhost:3000/dashboard/manufacturer/orders/{$orderId}";
    });

    Queue::assertPushed(SendMailJob::class, function (SendMailJob $job) use ($admin, $orderId): bool {
        return $job->recipient === $admin->email
            && $job->template === 'order-created-admin'
            && ($job->data['recipientRole'] ?? null) === 'admin'
            && ($job->data['ctaUrl'] ?? null) === "http://localhost:3000/admin/orders/{$orderId}";
    });
});

test('order created dispatches in-app notifications to buyer manufacturer and admins', function (): void {
    ['buyer' => $buyer, 'manufacturer' => $manufacturer, 'product' => $product] = seedManufacturerOrderScenario();
    $admin = User::factory()->admin()->create();

    Passport::actingAs($manufacturer);

    $response = $this->postJson('/api/v1/manufacturer/orders/create', buildManufacturerOrderCreatePayload(
        buyerId: $buyer->id,
        items: [[
            'product_id' => $product->id,
            'quantity' => 100,
            'unit_price' => 10,
        ]],
    ));

    $response->assertCreated();
    $orderId = (int) $response->json('data.id');

    Queue::assertPushed(SendOrderInAppNotificationJob::class, 3);

    Queue::assertPushed(SendOrderInAppNotificationJob::class, function (SendOrderInAppNotificationJob $job) use ($buyer, $orderId): bool {
        return $job->recipientId === $buyer->id
            && $job->type === 'order.created'
            && $job->actionUrl === "http://localhost:3000/dashboard/buyer/orders/{$orderId}";
    });

    Queue::assertPushed(SendOrderInAppNotificationJob::class, function (SendOrderInAppNotificationJob $job) use ($admin, $orderId): bool {
        return $job->recipientId === $admin->id
            && $job->type === 'order.created'
            && $job->actionUrl === "http://localhost:3000/admin/orders/{$orderId}";
    });

    Queue::assertPushed(SendOrderInAppNotificationJob::class, function (SendOrderInAppNotificationJob $job) use ($manufacturer, $orderId): bool {
        return $job->recipientId === $manufacturer->id
            && $job->type === 'order.created'
            && $job->actionUrl === "http://localhost:3000/dashboard/manufacturer/orders/{$orderId}";
    });
});

test('order created in-app notifications are persisted for buyer and admin', function (): void {
    ['buyer' => $buyer, 'manufacturer' => $manufacturer, 'product' => $product] = seedManufacturerOrderScenario();
    $admin = User::factory()->admin()->create();

    Passport::actingAs($manufacturer);

    $response = $this->postJson('/api/v1/manufacturer/orders/create', buildManufacturerOrderCreatePayload(
        buyerId: $buyer->id,
        items: [[
            'product_id' => $product->id,
            'quantity' => 100,
            'unit_price' => 10,
        ]],
    ));

    $response->assertCreated();
    $orderId = (int) $response->json('data.id');

    $jobs = Queue::pushed(SendOrderInAppNotificationJob::class);
    expect($jobs)->toHaveCount(3);

    foreach ($jobs as $job) {
        $job->handle(app(\App\Services\UserNotificationService::class));
    }

    expect(UserNotification::query()->where('user_id', $buyer->id)->where('type', 'order.created')->exists())->toBeTrue();
    expect(UserNotification::query()->where('user_id', $admin->id)->where('type', 'order.created')->exists())->toBeTrue();
    expect(UserNotification::query()->where('user_id', $manufacturer->id)->where('type', 'order.created')->exists())->toBeTrue();

    $buyerNotification = UserNotification::query()
        ->where('user_id', $buyer->id)
        ->where('type', 'order.created')
        ->first();

    expect($buyerNotification?->action_url)->toBe("http://localhost:3000/dashboard/buyer/orders/{$orderId}");
});

test('order status update dispatches in-app notifications to buyer and admins', function (): void {
    ['buyer' => $buyer, 'manufacturer' => $manufacturer, 'product' => $product] = seedManufacturerOrderScenario();
    $admin = User::factory()->admin()->create();

    Passport::actingAs($manufacturer);

    $createResponse = $this->postJson('/api/v1/manufacturer/orders/create', buildManufacturerOrderCreatePayload(
        buyerId: $buyer->id,
        items: [[
            'product_id' => $product->id,
            'quantity' => 100,
            'unit_price' => 10,
        ]],
    ));

    $orderId = (int) $createResponse->json('data.id');

    Queue::fake([SendMailJob::class, SendOrderInAppNotificationJob::class]);

    $this->postJson("/api/v1/manufacturer/orders/{$orderId}/status-updates", [
        'status' => 'shipped',
        'notes' => 'Package left the warehouse.',
    ])->assertCreated();

    Queue::assertPushed(SendOrderInAppNotificationJob::class, 3);

    Queue::assertPushed(SendOrderInAppNotificationJob::class, function (SendOrderInAppNotificationJob $job) use ($buyer, $orderId): bool {
        return $job->recipientId === $buyer->id
            && $job->type === 'order.status.shipped'
            && $job->actionUrl === "http://localhost:3000/dashboard/buyer/orders/{$orderId}";
    });

    Queue::assertPushed(SendOrderInAppNotificationJob::class, function (SendOrderInAppNotificationJob $job) use ($admin, $orderId): bool {
        return $job->recipientId === $admin->id
            && $job->type === 'order.status.shipped'
            && $job->actionUrl === "http://localhost:3000/admin/orders/{$orderId}";
    });

    Queue::assertPushed(SendOrderInAppNotificationJob::class, function (SendOrderInAppNotificationJob $job) use ($manufacturer, $orderId): bool {
        return $job->recipientId === $manufacturer->id
            && $job->type === 'order.status.shipped'
            && $job->actionUrl === "http://localhost:3000/dashboard/manufacturer/orders/{$orderId}";
    });
});

test('order status update queues emails for buyer and manufacturer only when shipped', function (): void {
    ['buyer' => $buyer, 'manufacturer' => $manufacturer, 'product' => $product] = seedManufacturerOrderScenario();
    $admin = User::factory()->admin()->create();

    Passport::actingAs($manufacturer);

    $createResponse = $this->postJson('/api/v1/manufacturer/orders/create', buildManufacturerOrderCreatePayload(
        buyerId: $buyer->id,
        items: [[
            'product_id' => $product->id,
            'quantity' => 100,
            'unit_price' => 10,
        ]],
    ));

    $orderId = (int) $createResponse->json('data.id');

    Queue::fake([SendMailJob::class, SendOrderInAppNotificationJob::class]);

    $this->postJson("/api/v1/manufacturer/orders/{$orderId}/status-updates", [
        'status' => 'shipped',
        'notes' => 'Package left the warehouse.',
    ])->assertCreated();

    Queue::assertPushed(SendMailJob::class, 2);

    Queue::assertPushed(SendMailJob::class, function (SendMailJob $job) use ($buyer, $orderId): bool {
        return $job->recipient === $buyer->email
            && $job->template === 'order-shipped-buyer'
            && ($job->data['ctaUrl'] ?? null) === "http://localhost:3000/dashboard/buyer/orders/{$orderId}";
    });

    Queue::assertPushed(SendMailJob::class, function (SendMailJob $job) use ($manufacturer, $orderId): bool {
        return $job->recipient === $manufacturer->email
            && $job->template === 'order-shipped-manufacturer'
            && ($job->data['ctaUrl'] ?? null) === "http://localhost:3000/dashboard/manufacturer/orders/{$orderId}";
    });

    Queue::assertNotPushed(SendMailJob::class, function (SendMailJob $job) use ($admin): bool {
        return $job->recipient === $admin->email;
    });
});

test('order completed queues emails for buyer manufacturer and admin with separate templates', function (): void {
    ['buyer' => $buyer, 'manufacturer' => $manufacturer, 'product' => $product] = seedManufacturerOrderScenario();
    $admin = User::factory()->admin()->create();

    Passport::actingAs($manufacturer);

    $createResponse = $this->postJson('/api/v1/manufacturer/orders/create', buildManufacturerOrderCreatePayload(
        buyerId: $buyer->id,
        items: [[
            'product_id' => $product->id,
            'quantity' => 100,
            'unit_price' => 10,
        ]],
    ));

    $orderId = (int) $createResponse->json('data.id');

    Queue::fake([SendMailJob::class, SendOrderInAppNotificationJob::class]);

    $this->postJson("/api/v1/manufacturer/orders/{$orderId}/status-updates", [
        'status' => 'completed',
        'notes' => 'Delivered to the buyer.',
    ])->assertCreated();

    Queue::assertPushed(SendMailJob::class, function (SendMailJob $job) use ($buyer): bool {
        return $job->recipient === $buyer->email
            && $job->template === 'order-completed-buyer';
    });

    Queue::assertPushed(SendMailJob::class, function (SendMailJob $job) use ($manufacturer): bool {
        return $job->recipient === $manufacturer->email
            && $job->template === 'order-completed-manufacturer';
    });

    Queue::assertPushed(SendMailJob::class, function (SendMailJob $job) use ($admin): bool {
        return $job->recipient === $admin->email
            && $job->template === 'order-completed-admin';
    });
});

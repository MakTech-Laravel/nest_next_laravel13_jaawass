<?php

namespace App\Services\Order;

use App\Enums\DashboardEventType;
use App\Enums\OrderStatus;
use App\Http\Requests\Api\V1\Manufacturer\Order\StoreOrderStatusUpdateRequest;
use App\Models\Order;
use App\Models\OrderStatusUpdate;
use App\Models\OrderStatusUpdateAttachment;
use App\Models\User;
use App\Services\Dashboard\EventTrackerService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class OrderStatusUpdateService
{
    public function __construct(
        private readonly EventTrackerService $eventTracker,
    ) {}

    public function create(
        Order $order,
        User $user,
        StoreOrderStatusUpdateRequest $request,
    ): Order {
        return DB::transaction(function () use ($order, $user, $request): Order {
            $validated = $request->validated();
            $status = OrderStatus::from($validated['status']);

            $update = OrderStatusUpdate::query()->create([
                'order_id' => $order->id,
                'user_id' => $user->id,
                'status' => $status->value,
                'notes' => $validated['notes'] ?? null,
            ]);

            $sourceLocale = $request->input('locale') ?? app()->getLocale();

            if (filled($validated['notes'] ?? null)) {
                $update->upsertTranslations([
                    $sourceLocale => ['notes' => $validated['notes']],
                ]);

                $update->autoTranslate(
                    sourceData: ['notes' => $validated['notes']],
                    sourceLocale: $sourceLocale,
                );
            }

            $this->storeAttachments($update, $request->file('photos', []), 'photo');
            $this->storeAttachments($update, $request->file('attachments', []), 'file');

            $orderUpdates = ['status' => $status->value];
            if ($status === OrderStatus::Completed && $order->delivered_at === null) {
                $orderUpdates['delivered_at'] = now();
            }

            $order->update($orderUpdates);

            if ($status === OrderStatus::Completed) {
                $counterparty = (int) $user->id === (int) $order->manufacturer_id
                    ? $order->buyer
                    : $order->manufacturer;

                $this->eventTracker->track(
                    eventType: DashboardEventType::OrderDelivered,
                    actor: $user,
                    entityType: 'order',
                    entityId: (int) $order->id,
                    counterparty: $counterparty,
                    metadata: [
                        'status' => $status->value,
                        'estimated_delivery_at' => $order->estimated_delivery_at?->toDateString(),
                    ],
                    occurredAt: $order->delivered_at ?? now(),
                );
            }

            return $this->loadOrderWithRelations($order->fresh());
        });
    }

    public function loadOrderWithRelations(Order $order): Order
    {
        return $order->load([
            ...$this->listRelations(),
            'statusUpdates' => fn ($query) => $query
                ->with(['user.company', 'attachments', 'translations'])
                ->latest('id'),
        ]);
    }

    /**
     * @return array<int|string, mixed>
     */
    public function listRelations(): array
    {
        return [
            'buyer.company',
            'manufacturer.company',
            'product.images',
            'product.category',
            'product.subCategory',
            'translations',
            'attachments',
            'statusUpdates' => fn ($query) => $query
                ->with(['user.company', 'attachments', 'translations'])
                ->latest('id'),
        ];
    }

    /**
     * @param  array<int, UploadedFile>  $files
     */
    private function storeAttachments(OrderStatusUpdate $update, array $files, string $type): void
    {
        if ($files === []) {
            return;
        }

        $disk = 'public';

        foreach ($files as $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            $path = $file->store(
                'order-status-updates/'.$update->order_id.'/'.$update->id.'/'.$type,
                ['disk' => $disk],
            );

            OrderStatusUpdateAttachment::query()->create([
                'order_status_update_id' => $update->id,
                'type' => $type,
                'disk' => $disk,
                'file_path' => $path,
                'file_mime' => (string) $file->getClientMimeType(),
                'original_name' => $file->getClientOriginalName(),
                'size_bytes' => $file->getSize() ?? Storage::disk($disk)->size($path),
            ]);
        }
    }
}

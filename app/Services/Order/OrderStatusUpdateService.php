<?php

namespace App\Services\Order;

use App\Enums\OrderStatus;
use App\Http\Requests\Api\V1\Manufacturer\Order\StoreOrderStatusUpdateRequest;
use App\Models\Order;
use App\Models\OrderStatusUpdate;
use App\Models\OrderStatusUpdateAttachment;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class OrderStatusUpdateService
{
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

            $order->update(['status' => $status->value]);

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

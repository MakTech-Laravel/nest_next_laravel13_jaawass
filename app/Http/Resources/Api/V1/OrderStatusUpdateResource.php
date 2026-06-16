<?php

namespace App\Http\Resources\Api\V1;

use App\Enums\OrderStatus;
use App\Models\OrderStatusUpdate;
use App\Support\Time\TimezoneFormatter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin OrderStatusUpdate
 */
class OrderStatusUpdateResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $locale = $request->query('locale') ?? app()->getLocale();
        $localized = $this->localizedData($locale);
        $status = OrderStatus::from($this->status);

        $photoAttachments = $this->relationLoaded('attachments')
            ? $this->attachments->where('type', 'photo')->values()
            : collect();

        return [
            'id' => $this->id,
            'status' => $status->value,
            'status_label' => $status->label(),
            'notes' => $localized['notes'],
            'created_at' => TimezoneFormatter::format($this->created_at),
            'updated_at' => TimezoneFormatter::format($this->updated_at),
            'author' => $this->whenLoaded('user', fn () => $this->user?->role?->value ?? 'manufacturer'),
            'user' => $this->whenLoaded('user', fn () => $this->user === null ? null : [
                'id' => $this->user->id,
                'first_name' => $this->user->first_name,
                'last_name' => $this->user->last_name,
                'email' => $this->user->email,
                'role' => $this->user->role?->value,
                'role_label' => $this->user->role?->label(),
                'company_name' => $this->user->company?->company_name,
            ]),
            'photos' => $this->relationLoaded('attachments')
                ? $photoAttachments->map(fn ($attachment) => $attachment->url)->values()->all()
                : [],
            'attachments' => $this->relationLoaded('attachments')
                ? OrderStatusUpdateAttachmentResource::collection($this->attachments->where('type', 'file')->values())
                : [],
        ];
    }
}

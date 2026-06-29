<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Admin;

use App\Http\Resources\Api\V1\MessageResource;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Conversation
 */
class AdminConversationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'last_message_sent_at' => $this->when(
                array_key_exists('last_message_sent_at', $this->resource->getAttributes()),
                fn () => $this->resource->getAttribute('last_message_sent_at')
            ),
            'creator' => $this->whenLoaded('creator', fn () => $this->creator === null ? null : [
                'id' => $this->creator->id,
                'first_name' => $this->creator->first_name,
                'last_name' => $this->creator->last_name,
                'email' => $this->creator->email,
                'role' => $this->creator->role->value,
            ]),
            'participants' => $this->whenLoaded('participants', function () {
                return $this->participants->map(function (User $user): array {
                    return [
                        'id' => $user->id,
                        'first_name' => $user->first_name,
                        'last_name' => $user->last_name,
                        'email' => $user->email,
                        'role' => $user->role->value,
                        'avatar' => storage_url($user->avatar),
                        'company_name' => $user->relationLoaded('company') ? $user->company?->company_name : null,
                        'country' => $user->relationLoaded('company') ? $user->company?->country : null,
                    ];
                });
            }),
            'last_message' => $this->whenLoaded('latestMessage', function () {
                return $this->latestMessage === null
                    ? null
                    : new MessageResource($this->latestMessage);
            }),
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Admin\AdminConversationResource;
use App\Http\Resources\Api\V1\MessageResource;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as HttpStatus;

class ConversationAdminController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'search' => ['sometimes', 'string', 'max:255'],
        ]);

        $perPage = (int) ($validated['per_page'] ?? 20);
        $search = isset($validated['search']) ? trim((string) $validated['search']) : '';

        $query = Conversation::query()
            ->buyerManufacturerOnly()
            ->addSelect([
                'last_message_sent_at' => Message::query()
                    ->select('created_at')
                    ->whereColumn('messages.conversation_id', 'conversations.id')
                    ->orderByDesc('id')
                    ->limit(1),
            ])
            ->with([
                'participants.company',
                'creator',
                'latestMessage.sender',
                'latestMessage.attachments',
            ])
            ->orderByDesc('last_message_sent_at')
            ->orderByDesc('id');

        if ($search !== '') {
            $like = '%'.$search.'%';
            $query->where(function ($builder) use ($like): void {
                $builder->whereHas('participants', function ($participantQuery) use ($like): void {
                    $participantQuery
                        ->where('first_name', 'like', $like)
                        ->orWhere('last_name', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhereHas('company', function ($companyQuery) use ($like): void {
                            $companyQuery->where('company_name', 'like', $like);
                        });
                });
            });
        }

        $conversations = $query
            ->paginate($perPage)
            ->withQueryString();

        return sendResponse(
            status: true,
            message: __('api.admin_conversations_fetched_successfully'),
            data: AdminConversationResource::collection($conversations),
            statusCode: HttpStatus::HTTP_OK
        );
    }

    public function show(Conversation $conversation): JsonResponse
    {
        if (! $this->isBuyerManufacturerConversation($conversation)) {
            return $this->forbiddenResponse();
        }

        $conversation->load([
            'participants.company',
            'creator',
            'latestMessage.sender',
            'latestMessage.attachments',
        ]);

        return sendResponse(
            status: true,
            message: __('api.admin_conversation_fetched_successfully'),
            data: new AdminConversationResource($conversation),
            statusCode: HttpStatus::HTTP_OK
        );
    }

    public function messages(Request $request, Conversation $conversation): JsonResponse
    {
        if (! $this->isBuyerManufacturerConversation($conversation)) {
            return $this->forbiddenResponse();
        }

        $validated = $request->validate([
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ]);

        $perPage = (int) ($validated['per_page'] ?? 50);

        $messages = $conversation->messages()
            ->with(['sender', 'attachments'])
            ->orderBy('id')
            ->paginate($perPage)
            ->withQueryString();

        return sendResponse(
            status: true,
            message: __('api.admin_conversation_messages_fetched_successfully'),
            data: MessageResource::collection($messages),
            statusCode: HttpStatus::HTTP_OK
        );
    }

    private function isBuyerManufacturerConversation(Conversation $conversation): bool
    {
        if ($conversation->relationLoaded('participants')) {
            return $conversation->isBuyerManufacturerChat();
        }

        return Conversation::query()
            ->whereKey($conversation->id)
            ->buyerManufacturerOnly()
            ->exists();
    }

    private function forbiddenResponse(): JsonResponse
    {
        return sendResponse(
            status: false,
            message: __('api.admin_conversation_forbidden'),
            data: null,
            statusCode: HttpStatus::HTTP_FORBIDDEN
        );
    }
}

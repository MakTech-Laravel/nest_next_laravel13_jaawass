<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\TicketStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\IndexTicketRequest;
use App\Http\Requests\Api\V1\Admin\StoreTicketMessageRequest;
use App\Http\Requests\Api\V1\Admin\UpdateTicketRequest;
use App\Http\Resources\Api\V1\Admin\TicketAdminResource;
use App\Models\Ticket;
use App\Services\TicketMessageService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response as HttpStatus;

class TicketAdminController extends Controller
{
    public function __construct(
        private readonly TicketMessageService $ticketMessageService,
    ) {}

    public function index(IndexTicketRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $tickets = $this->ticketListQuery($validated)
            ->paginate((int) ($validated['per_page'] ?? 15))
            ->withQueryString();

        return sendResponse(
            status: true,
            message: __('api.admin_tickets_fetched_successfully'),
            data: TicketAdminResource::collection($tickets),
            statusCode: HttpStatus::HTTP_OK
        );
    }

    public function show(Ticket $ticket): JsonResponse
    {
        $ticket->load([
            'user',
            'assignee',
            'messages' => fn ($query) => $query->orderBy('id')->with(['user', 'attachments', 'translations']),
        ]);

        return sendResponse(
            status: true,
            message: __('api.admin_ticket_fetched_successfully'),
            data: new TicketAdminResource($ticket),
            statusCode: HttpStatus::HTTP_OK
        );
    }

    public function update(UpdateTicketRequest $request, Ticket $ticket): JsonResponse
    {
        $ticket->update($request->validated());

        $ticket->load(['user', 'assignee']);

        return sendResponse(
            status: true,
            message: __('api.admin_ticket_updated_successfully'),
            data: new TicketAdminResource($ticket),
            statusCode: HttpStatus::HTTP_OK
        );
    }

    public function storeMessage(StoreTicketMessageRequest $request, Ticket $ticket): JsonResponse
    {
        $this->ticketMessageService->sendMessage(
            $ticket,
            $request->user(),
            $request->input('message'),
            $request->file('attachments', []),
            $request->input('locale'),
        );

        if ($ticket->status !== TicketStatus::Closed) {
            $ticket->forceFill(['status' => TicketStatus::WaitingOnCustomer->value])->save();
        }

        $ticket->load([
            'user',
            'assignee',
            'messages' => fn ($query) => $query->orderBy('id')->with(['user', 'attachments', 'translations']),
        ]);

        return sendResponse(
            status: true,
            message: __('api.admin_ticket_message_sent_successfully'),
            data: new TicketAdminResource($ticket),
            statusCode: HttpStatus::HTTP_CREATED
        );
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function ticketListQuery(array $validated): Builder
    {
        $query = Ticket::query()
            ->with(['user', 'assignee'])
            ->latest('id');

        if (isset($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (isset($validated['priority'])) {
            $query->where('priority', $validated['priority']);
        }

        if (isset($validated['department_type'])) {
            $query->where('department_type', $validated['department_type']);
        }

        if (isset($validated['assigned_to'])) {
            $query->where('assigned_to', $validated['assigned_to']);
        }

        if (isset($validated['user_id'])) {
            $query->where('user_id', $validated['user_id']);
        }

        if (! empty($validated['search'])) {
            $searchTerm = trim((string) $validated['search']);

            $query->where(function (Builder $builder) use ($searchTerm): void {
                $builder
                    ->where('subject', 'like', "%{$searchTerm}%")
                    ->orWhereHas('user', function (Builder $userQuery) use ($searchTerm): void {
                        $userQuery
                            ->where('first_name', 'like', "%{$searchTerm}%")
                            ->orWhere('last_name', 'like', "%{$searchTerm}%")
                            ->orWhere('email', 'like', "%{$searchTerm}%");
                    });
            });
        }

        return $query;
    }
}

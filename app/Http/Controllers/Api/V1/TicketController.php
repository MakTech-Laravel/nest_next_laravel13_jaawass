<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\TicketDepartmentType;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\IndexTicketRequest;
use App\Http\Requests\Api\V1\StoreTicketMessageRequest;
use App\Http\Requests\Api\V1\StoreTicketRequest;
use App\Http\Resources\Api\V1\TicketResource;
use App\Models\Ticket;
use App\Services\TicketMessageService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response as HttpStatus;

class TicketController extends Controller
{
    public function __construct(
        private readonly TicketMessageService $ticketMessageService,
    ) {}

    public function options(): JsonResponse
    {
        return sendResponse(
            status: true,
            message: __('api.ticket_options_fetched_successfully'),
            data: [
                'statuses' => $this->mapEnumOptions(TicketStatus::cases()),
                'priorities' => $this->mapEnumOptions(TicketPriority::cases()),
                'department_types' => $this->mapEnumOptions(TicketDepartmentType::cases()),
            ],
            statusCode: HttpStatus::HTTP_OK
        );
    }

    public function index(IndexTicketRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $tickets = $this->ticketListQuery((int) $request->user()->id, $validated)
            ->paginate((int) ($validated['per_page'] ?? 15))
            ->withQueryString();

        return sendResponse(
            status: true,
            message: __('api.tickets_fetched_successfully'),
            data: TicketResource::collection($tickets),
            statusCode: HttpStatus::HTTP_OK
        );
    }

    public function store(StoreTicketRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user = $request->user();

        $ticket = DB::transaction(function () use ($validated, $request, $user): Ticket {
            $ticket = Ticket::query()->create([
                'user_id' => $user->id,
                'subject' => $validated['subject'],
                'department_type' => $validated['department_type'],
                'priority' => $validated['priority'] ?? TicketPriority::Medium->value,
                'status' => TicketStatus::Open->value,
            ]);

            $this->ticketMessageService->sendMessage(
                $ticket,
                $user,
                $validated['message'],
                $request->file('attachments', []),
                $request->input('locale'),
            );

            return $ticket;
        });

        $ticket->load([
            'assignee',
            'messages' => fn ($query) => $query->orderBy('id')->with(['user', 'attachments', 'translations']),
        ]);

        return sendResponse(
            status: true,
            message: __('api.ticket_created_successfully'),
            data: new TicketResource($ticket),
            statusCode: HttpStatus::HTTP_CREATED
        );
    }

    public function show(IndexTicketRequest $request, int $ticket): JsonResponse
    {
        $ticketModel = Ticket::query()
            ->where('user_id', $request->user()->id)
            ->with([
                'assignee',
                'messages' => fn ($query) => $query->orderBy('id')->with(['user', 'attachments', 'translations']),
            ])
            ->findOrFail($ticket);

        return sendResponse(
            status: true,
            message: __('api.ticket_fetched_successfully'),
            data: new TicketResource($ticketModel),
            statusCode: HttpStatus::HTTP_OK
        );
    }

    public function storeMessage(StoreTicketMessageRequest $request, Ticket $ticket): JsonResponse
    {
        if ((int) $ticket->user_id !== (int) $request->user()->id) {
            return sendResponse(
                status: false,
                message: __('api.ticket_forbidden'),
                data: null,
                statusCode: HttpStatus::HTTP_FORBIDDEN
            );
        }

        if (in_array($ticket->status, [TicketStatus::Closed, TicketStatus::Resolved], true)) {
            return sendResponse(
                status: false,
                message: __('api.ticket_closed_cannot_reply'),
                data: null,
                statusCode: HttpStatus::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $this->ticketMessageService->sendMessage(
            $ticket,
            $request->user(),
            $request->input('message'),
            $request->file('attachments', []),
            $request->input('locale'),
        );

        if ($ticket->status === TicketStatus::WaitingOnCustomer) {
            $ticket->forceFill(['status' => TicketStatus::Open->value])->save();
        }

        return sendResponse(
            status: true,
            message: __('api.ticket_message_sent_successfully'),
            data: new TicketResource($ticket->fresh([
                'assignee',
                'messages' => fn ($query) => $query->orderBy('id')->with(['user', 'attachments', 'translations']),
            ])),
            statusCode: HttpStatus::HTTP_CREATED
        );
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function ticketListQuery(int $userId, array $validated): Builder
    {
        $query = Ticket::query()
            ->where('user_id', $userId)
            ->with(['assignee'])
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

        if (! empty($validated['search'])) {
            $searchTerm = trim((string) $validated['search']);
            $query->where('subject', 'like', "%{$searchTerm}%");
        }

        return $query;
    }

    /**
     * @param  array<int, TicketStatus|TicketPriority|TicketDepartmentType>  $cases
     * @return list<array{value: string, label: string}>
     */
    private function mapEnumOptions(array $cases): array
    {
        return array_map(
            fn (TicketStatus|TicketPriority|TicketDepartmentType $case) => [
                'value' => $case->value,
                'label' => $case->label(),
            ],
            $cases
        );
    }
}

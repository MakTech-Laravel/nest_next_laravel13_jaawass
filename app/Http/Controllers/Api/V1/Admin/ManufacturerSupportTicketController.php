<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\TicketDepartmentType;
use App\Enums\TicketPriority;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\StoreManufacturerSupportTicketRequest;
use App\Http\Resources\Api\V1\Admin\TicketAdminResource;
use App\Models\User;
use App\Services\Manufacturer\ManufacturerAdminMessageService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response as HttpStatus;

class ManufacturerSupportTicketController extends Controller
{
    public function __construct(
        private readonly ManufacturerAdminMessageService $messageService,
    ) {}

    public function store(StoreManufacturerSupportTicketRequest $request, int $manufacturer): JsonResponse
    {
        $manufacturerUser = User::query()
            ->where('id', $manufacturer)
            ->where('role', 'manufacturer')
            ->with('company')
            ->first();

        if ($manufacturerUser === null) {
            return sendResponse(
                status: false,
                message: __('common.not_found'),
                data: null,
                statusCode: HttpStatus::HTTP_NOT_FOUND,
            );
        }

        $validated = $request->validated();

        $ticket = $this->messageService->createSupportTicket(
            manufacturer: $manufacturerUser,
            admin: $request->user(),
            subject: $validated['subject'],
            message: $validated['message'],
            department: TicketDepartmentType::from($validated['department_type'] ?? TicketDepartmentType::Account->value),
            priority: TicketPriority::from($validated['priority'] ?? TicketPriority::Medium->value),
            attachments: $request->file('attachments', []),
        );

        return sendResponse(
            status: true,
            message: __('manufacturer_support_ticket.created'),
            data: new TicketAdminResource($ticket),
            statusCode: HttpStatus::HTTP_CREATED,
        );
    }
}

<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\SendManufacturerMessageRequest;
use App\Http\Resources\Api\V1\Admin\TicketAdminResource;
use App\Models\User;
use App\Services\Manufacturer\ManufacturerAdminMessageService;
use Symfony\Component\HttpFoundation\Response as HttpStatus;

class ManufacturerAdminMessageController extends Controller
{
    public function __construct(
        private readonly ManufacturerAdminMessageService $messageService,
    ) {}

    public function store(SendManufacturerMessageRequest $request, int $manufacturer)
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
                data: [],
                statusCode: HttpStatus::HTTP_NOT_FOUND,
            );
        }

        $validated = $request->validated();

        $ticket = $this->messageService->sendMessage(
            manufacturer: $manufacturerUser,
            admin: $request->user(),
            message: $validated['message'],
            subject: $validated['subject'] ?? null,
        );

        return sendResponse(
            status: true,
            message: __('manufacturer_admin_message.sent'),
            data: new TicketAdminResource($ticket),
            statusCode: HttpStatus::HTTP_CREATED,
        );
    }
}

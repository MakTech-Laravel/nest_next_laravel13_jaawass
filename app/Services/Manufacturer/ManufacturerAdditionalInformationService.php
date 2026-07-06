<?php

namespace App\Services\Manufacturer;

use App\Actions\Api\V1\Admin\Users\UpdateManufactureStatusAction;
use App\Enums\AdditionalInformationRequestStatus;
use App\Enums\AdditionalInformationType;
use App\Enums\MailTemplate;
use App\Enums\TicketDepartmentType;
use App\Enums\TicketStatus;
use App\Enums\UserManuFactureStatus;
use App\Http\Requests\Api\V1\Admin\IndexAllManufacturerAdditionalInformationRequest;
use App\Models\ManufacturerAdditionalInformationRequest;
use App\Models\ManufacturerAdditionalInformationResponse;
use App\Models\Ticket;
use App\Models\User;
use App\Services\Mailing\MailingService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ManufacturerAdditionalInformationService
{
    public function __construct(
        private readonly MailingService $mailingService,
        private readonly ManufacturerRegistrationTicketService $ticketService,
        private readonly ManufacturerAdditionalInformationNotificationService $notificationService,
        private readonly UpdateManufactureStatusAction $updateManufactureStatusAction,
    ) {}

    /**
     * @param  list<string>  $allowedTypes
     */
    public function createRequest(User $manufacturer, User $admin, string $message, array $allowedTypes): ManufacturerAdditionalInformationRequest
    {
        $normalizedTypes = $this->normalizeAllowedTypes($allowedTypes);

        $ticket = $this->ticketService->createForManufacturer(
            manufacturer: $manufacturer,
            admin: $admin,
            subject: __('manufacturer_additional_information.ticket_subject'),
            message: $message,
            department: TicketDepartmentType::Account,
            status: TicketStatus::WaitingOnCustomer,
        );

        $request = ManufacturerAdditionalInformationRequest::query()->create([
            'user_id' => $manufacturer->id,
            'requested_by' => $admin->id,
            'ticket_id' => $ticket->id,
            'token' => Str::random(64),
            'message' => $message,
            'allowed_types' => $normalizedTypes,
            'status' => AdditionalInformationRequestStatus::Pending,
            'expires_at' => now()->addDays((int) config('manufacturer_additional_information.expires_days', 7)),
        ]);

        $manufacturer->loadMissing('company');

        $this->mailingService->send(
            $manufacturer->email,
            MailTemplate::ManufacturerAdditionalInformation,
            [
                'manufacturerName' => trim($manufacturer->first_name.' '.$manufacturer->last_name) ?: 'there',
                'companyName' => $manufacturer->company?->company_name ?? config('app.name'),
                'adminMessage' => $message,
                'allowedTypes' => collect($normalizedTypes)
                    ->map(fn (string $type) => AdditionalInformationType::from($type)->label())
                    ->values()
                    ->all(),
                'submissionUrl' => $this->submissionUrl($request->token),
                'expiresAt' => $request->expires_at->format('F j, Y'),
                'requestedAt' => $request->created_at->format('F j, Y'),
                'referenceId' => sprintf('SN-MFR-%06d', $request->id),
            ],
        );

        return $request->load(['requestedBy', 'responses']);
    }

    public function findByToken(string $token): ManufacturerAdditionalInformationRequest
    {
        $request = ManufacturerAdditionalInformationRequest::query()
            ->with(['manufacturer.company', 'responses', 'requestedBy'])
            ->where('token', $token)
            ->first();

        if ($request === null) {
            throw new NotFoundHttpException(__('manufacturer_additional_information.invalid_token'));
        }

        if ($request->isExpired() && $request->status === AdditionalInformationRequestStatus::Pending) {
            $request->update(['status' => AdditionalInformationRequestStatus::Expired]);
            $request->refresh();
        }

        return $request;
    }

    public function findSubmittableByToken(string $token): ManufacturerAdditionalInformationRequest
    {
        $request = $this->findByToken($token);

        if ($request->status === AdditionalInformationRequestStatus::Submitted) {
            throw ValidationException::withMessages([
                'token' => [__('manufacturer_additional_information.already_submitted')],
            ]);
        }

        if (in_array($request->status, [
            AdditionalInformationRequestStatus::Accepted,
            AdditionalInformationRequestStatus::Rejected,
        ], true)) {
            throw ValidationException::withMessages([
                'token' => [__('manufacturer_additional_information.not_submittable')],
            ]);
        }

        if ($request->status === AdditionalInformationRequestStatus::Expired || $request->isExpired()) {
            throw ValidationException::withMessages([
                'token' => [__('manufacturer_additional_information.expired')],
            ]);
        }

        return $request;
    }

    /**
     * @param  array<int, array{type: string, message?: string|null, file?: UploadedFile|null}>  $items
     */
    public function submitResponses(ManufacturerAdditionalInformationRequest $request, array $items): ManufacturerAdditionalInformationRequest
    {
        if (! $request->isSubmittable()) {
            throw ValidationException::withMessages([
                'token' => [__('manufacturer_additional_information.not_submittable')],
            ]);
        }

        if ($items === []) {
            throw ValidationException::withMessages([
                'responses' => [__('manufacturer_additional_information.responses_required')],
            ]);
        }

        DB::transaction(function () use ($request, $items): void {
            foreach ($items as $index => $item) {
                $type = AdditionalInformationType::from($item['type']);

                if (! in_array($type->value, $request->allowed_types, true)) {
                    throw ValidationException::withMessages([
                        "responses.{$index}.type" => [__('manufacturer_additional_information.type_not_allowed')],
                    ]);
                }

                $message = isset($item['message']) ? trim((string) $item['message']) : null;
                $file = $this->resolveUploadedFile($type, $item);

                if ($type === AdditionalInformationType::Text) {
                    if ($message === null || $message === '') {
                        throw ValidationException::withMessages([
                            "responses.{$index}.message" => [__('manufacturer_additional_information.text_required')],
                        ]);
                    }
                } elseif (! $file instanceof UploadedFile) {
                    throw ValidationException::withMessages([
                        "responses.{$index}.file" => [__('manufacturer_additional_information.file_required')],
                    ]);
                } else {
                    $this->validateUploadedFile($type, $file, $index);
                }

                $storedPath = null;
                $originalName = null;
                $mimeType = null;
                $fileSize = null;

                if ($file instanceof UploadedFile) {
                    $storedPath = $file->store(
                        $this->storagePathForType($type),
                        'public',
                    );
                    $originalName = $file->getClientOriginalName();
                    $mimeType = $file->getMimeType();
                    $fileSize = $file->getSize();
                }

                ManufacturerAdditionalInformationResponse::query()->create([
                    'request_id' => $request->id,
                    'type' => $type,
                    'message' => $message,
                    'file_path' => $storedPath,
                    'original_name' => $originalName,
                    'mime_type' => $mimeType,
                    'file_size' => $fileSize,
                ]);
            }

            $request->update([
                'status' => AdditionalInformationRequestStatus::Submitted,
                'submitted_at' => now(),
            ]);
        });

        $submitted = $request->fresh(['manufacturer.company', 'requestedBy', 'responses']);

        $this->notificationService->notifyAdminOfSubmission($submitted);

        return $submitted;
    }

    public function reviewSubmission(
        ManufacturerAdditionalInformationRequest $request,
        User $admin,
        string $action,
        ?string $notes = null,
        ?string $reason = null,
    ): ManufacturerAdditionalInformationRequest {
        if ($request->status !== AdditionalInformationRequestStatus::Submitted) {
            throw ValidationException::withMessages([
                'action' => [__('manufacturer_additional_information.not_reviewable')],
            ]);
        }

        $manufacturer = $request->manufacturer()->with('company')->first();

        if ($manufacturer === null) {
            throw new NotFoundHttpException(__('common.not_found'));
        }

        DB::transaction(function () use ($request, $admin, $action, $notes, $reason, $manufacturer): void {
            if ($action === 'accept') {
                $request->update([
                    'status' => AdditionalInformationRequestStatus::Accepted,
                    'reviewed_by' => $admin->id,
                    'reviewed_at' => now(),
                    'review_notes' => $notes,
                ]);

                $this->updateManufactureStatusAction->handle(
                    $manufacturer,
                    UserManuFactureStatus::APPROVED,
                    null,
                );

                $this->updateLinkedTicketStatus($request, TicketStatus::Resolved);
            } else {
                if ($reason === null || $reason === '') {
                    throw ValidationException::withMessages([
                        'reason' => [__('manufacturer_additional_information.rejection_reason_required')],
                    ]);
                }

                $request->update([
                    'status' => AdditionalInformationRequestStatus::Rejected,
                    'reviewed_by' => $admin->id,
                    'reviewed_at' => now(),
                    'review_notes' => $reason,
                ]);

                $this->updateManufactureStatusAction->handle(
                    $manufacturer,
                    UserManuFactureStatus::REJECTED,
                    $reason,
                );

                $this->updateLinkedTicketStatus($request, TicketStatus::Closed);
            }
        });

        return $request->fresh(['requestedBy', 'reviewedBy', 'responses', 'manufacturer.company']);
    }

    public function paginateForAdmin(IndexAllManufacturerAdditionalInformationRequest $request): LengthAwarePaginator
    {
        $query = ManufacturerAdditionalInformationRequest::query()
            ->with(['requestedBy', 'responses', 'manufacturer.company'])
            ->withCount('responses')
            ->whereHas('manufacturer', fn ($builder) => $builder->where('role', 'manufacturer'));

        $status = $request->statusFilter();

        if ($status === null) {
            $query->whereIn('status', [
                AdditionalInformationRequestStatus::Pending->value,
                AdditionalInformationRequestStatus::Submitted->value,
            ]);
        } elseif ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($request->unverifiedOnly()) {
            $query->whereHas(
                'manufacturer',
                fn ($builder) => $builder->where('manufacture_status', UserManuFactureStatus::PENDING->value)
            );
        }

        if ($search = $request->searchTerm()) {
            $query->where(function ($builder) use ($search): void {
                $builder
                    ->where('message', 'like', "%{$search}%")
                    ->orWhereHas('manufacturer', function ($manufacturerQuery) use ($search): void {
                        $manufacturerQuery
                            ->where('email', 'like', "%{$search}%")
                            ->orWhere('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhereHas('company', fn ($companyQuery) => $companyQuery
                                ->where('company_name', 'like', "%{$search}%"));
                    });
            });
        }

        return $query
            ->latest()
            ->paginate(
                perPage: $request->perPage(),
                pageName: 'page',
                page: $request->pageNumber(),
            );
    }

    public function adminReviewUrl(ManufacturerAdditionalInformationRequest $informationRequest): string
    {
        $frontendUrl = rtrim((string) config('app.frontend_url', config('app.url')), '/');

        if ($informationRequest->ticket_id !== null) {
            return "{$frontendUrl}/admin/customer-supports/tickets/{$informationRequest->ticket_id}";
        }

        return "{$frontendUrl}/admin/manufacturer-registrations?manufacturer={$informationRequest->user_id}";
    }

    public function referenceId(ManufacturerAdditionalInformationRequest $informationRequest): string
    {
        return sprintf('SN-MFR-%06d', $informationRequest->id);
    }

    /**
     * @param  list<string>  $allowedTypes
     * @return list<string>
     */
    private function normalizeAllowedTypes(array $allowedTypes): array
    {
        $normalized = array_values(array_unique(array_map('strval', $allowedTypes)));

        if ($normalized === []) {
            throw ValidationException::withMessages([
                'allowed_types' => [__('manufacturer_additional_information.types_required')],
            ]);
        }

        foreach ($normalized as $type) {
            if (! in_array($type, AdditionalInformationType::values(), true)) {
                throw ValidationException::withMessages([
                    'allowed_types' => [__('manufacturer_additional_information.invalid_type')],
                ]);
            }
        }

        return $normalized;
    }

    /**
     * @param  array{type: string, message?: string|null, file?: UploadedFile|null, video?: UploadedFile|null}  $item
     */
    private function resolveUploadedFile(AdditionalInformationType $type, array $item): ?UploadedFile
    {
        $file = $item['file'] ?? null;

        if ($file instanceof UploadedFile) {
            return $file;
        }

        if ($type === AdditionalInformationType::Video) {
            $video = $item['video'] ?? null;

            return $video instanceof UploadedFile ? $video : null;
        }

        return null;
    }

    private function storagePathForType(AdditionalInformationType $type): string
    {
        return (string) config(
            "manufacturer_additional_information.storage_paths.{$type->value}",
            config('manufacturer_additional_information.submission_path'),
        );
    }

    private function validateUploadedFile(AdditionalInformationType $type, UploadedFile $file, int $index): void
    {
        $maxKb = (int) config("manufacturer_additional_information.max_file_sizes_kb.{$type->value}", 10240);
        $allowedMimes = config("manufacturer_additional_information.allowed_mimes.{$type->value}", []);

        if ($file->getSize() > ($maxKb * 1024)) {
            throw ValidationException::withMessages([
                "responses.{$index}.file" => [__('manufacturer_additional_information.file_too_large')],
            ]);
        }

        $extension = strtolower($file->getClientOriginalExtension());
        $mime = strtolower((string) $file->getMimeType());

        $mimeAllowed = collect($allowedMimes)->contains(function (string $allowed) use ($extension, $mime): bool {
            return str_contains($mime, $allowed) || $extension === $allowed;
        });

        if (! $mimeAllowed) {
            throw ValidationException::withMessages([
                "responses.{$index}.file" => [__('manufacturer_additional_information.invalid_file_type')],
            ]);
        }
    }

    private function updateLinkedTicketStatus(
        ManufacturerAdditionalInformationRequest $request,
        TicketStatus $status,
    ): void {
        if ($request->ticket_id === null) {
            return;
        }

        Ticket::query()
            ->whereKey($request->ticket_id)
            ->update(['status' => $status]);
    }

    private function submissionUrl(string $token): string
    {
        $frontendUrl = rtrim((string) config('app.frontend_url', config('app.url')), '/');

        return "{$frontendUrl}/review?token={$token}";
    }
}

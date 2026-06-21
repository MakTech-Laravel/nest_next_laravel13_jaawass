<?php

namespace App\Services\Manufacturer;

use App\Enums\AdditionalInformationRequestStatus;
use App\Enums\AdditionalInformationType;
use App\Enums\MailTemplate;
use App\Models\ManufacturerAdditionalInformationRequest;
use App\Models\ManufacturerAdditionalInformationResponse;
use App\Models\User;
use App\Services\Mailing\MailingService;
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
    ) {}

    /**
     * @param  list<string>  $allowedTypes
     */
    public function createRequest(User $manufacturer, User $admin, string $message, array $allowedTypes): ManufacturerAdditionalInformationRequest
    {
        $normalizedTypes = $this->normalizeAllowedTypes($allowedTypes);

        $request = ManufacturerAdditionalInformationRequest::query()->create([
            'user_id' => $manufacturer->id,
            'requested_by' => $admin->id,
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

        return $request->fresh(['manufacturer.company', 'requestedBy', 'responses']);
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

    private function submissionUrl(string $token): string
    {
        $frontendUrl = rtrim((string) config('app.frontend_url', config('app.url')), '/');

        return "{$frontendUrl}/manufacturer/submit-information?token={$token}";
    }
}

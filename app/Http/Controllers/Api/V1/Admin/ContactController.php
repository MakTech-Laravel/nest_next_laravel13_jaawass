<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\IndexContactRequest;
use App\Http\Requests\Api\V1\Admin\UpdateContactReadStatusRequest;
use App\Http\Requests\Api\V1\StoreContactRequest;
use App\Http\Resources\Api\V1\ContactResource;
use App\Models\Contact;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response as HttpStatus;

class ContactController extends Controller
{
    public function store(StoreContactRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $contact = Contact::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'company_name' => $validated['company_name'],
            'inquiry_type' => $validated['inquiry_type'],
            'message' => $validated['message'],
            'is_read' => false,
        ]);

        $sourceLocale = $request->input('locale') ?? app()->getLocale();
        $sourceData = [
            'message' => $validated['message'],
        ];

        $contact->upsertTranslations([
            $sourceLocale => $sourceData,
        ]);

        $contact->autoTranslate(
            sourceData: $sourceData,
            sourceLocale: $sourceLocale,
        );

        $contact->load('translations');

        return sendResponse(
            status: true,
            message: __('api.contact_created_successfully'),
            data: new ContactResource($contact),
            statusCode: HttpStatus::HTTP_CREATED
        );
    }

    public function index(IndexContactRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $contacts = $this->contactListQuery($validated)
            ->paginate((int) ($validated['per_page'] ?? 15))
            ->withQueryString();

        return sendResponse(
            status: true,
            message: __('api.admin_contacts_fetched_successfully'),
            data: ContactResource::collection($contacts),
            statusCode: HttpStatus::HTTP_OK
        );
    }

    public function show(Contact $contact): JsonResponse
    {
        $contact->load('translations');

        return sendResponse(
            status: true,
            message: __('api.admin_contact_fetched_successfully'),
            data: new ContactResource($contact),
            statusCode: HttpStatus::HTTP_OK
        );
    }

    public function destroy(Contact $contact): JsonResponse
    {
        $contact->load('translations');

        $contactData = new ContactResource($contact);
        $contact->delete();

        return sendResponse(
            status: true,
            message: __('common.deleted'),
            data: $contactData,
            statusCode: HttpStatus::HTTP_OK
        );
    }

    public function updateReadStatus(UpdateContactReadStatusRequest $request, Contact $contact): JsonResponse
    {
        $contact->update([
            'is_read' => $request->boolean('is_read'),
        ]);

        $contact->load('translations');

        return sendResponse(
            status: true,
            message: __('api.admin_contact_read_status_updated_successfully'),
            data: new ContactResource($contact),
            statusCode: HttpStatus::HTTP_OK
        );
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function contactListQuery(array $validated): Builder
    {
        $query = Contact::query()
            ->with('translations')
            ->latest('id');

        if (array_key_exists('is_read', $validated)) {
            $query->where('is_read', $validated['is_read']);
        }

        if (! empty($validated['search'])) {
            $searchTerm = trim((string) $validated['search']);

            $query->where(function (Builder $builder) use ($searchTerm): void {
                $builder
                    ->where('name', 'like', "%{$searchTerm}%")
                    ->orWhere('email', 'like', "%{$searchTerm}%")
                    ->orWhere('company_name', 'like', "%{$searchTerm}%")
                    ->orWhere('inquiry_type', 'like', "%{$searchTerm}%")
                    ->orWhere('message', 'like', "%{$searchTerm}%");
            });
        }

        return $query;
    }
}

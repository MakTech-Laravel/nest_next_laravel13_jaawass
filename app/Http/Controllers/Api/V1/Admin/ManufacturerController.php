<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\Api\V1\Admin\Users\UpdateManufactureStatusAction;
use App\Enums\AdditionalInformationRequestStatus;
use App\Enums\DashboardEventType;
use App\Enums\UserManuFactureStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\StoreManufacturerRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Jobs\SendManufacturerAccountDetailJob;
use App\Models\User;
use App\Services\Company\CompanySlugService;
use App\Services\Dashboard\EventTrackerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response as HttpStatus;

class ManufacturerController extends Controller
{
    public function __construct(
        private readonly EventTrackerService $eventTracker,
        private readonly UpdateManufactureStatusAction $updateManufactureStatusAction,
    ) {}

    public function index()
    {
        $query = User::isManufacturer()->with([
            'company.industries',
            'manufacturerReviews',
            'factoryImages',
            'preferredCurrency',
            'subscription.plan',
            'subscriptionLogs.fromPlan',
            'subscriptionLogs.toPlan',
            ...$this->manufacturerRelations(),
            'subscription',
            'subscriptionLogs',
        ]);
        if(request()->has('search')) {
            $query->where('first_name', 'like', '%' . request()->input('search') . '%')
                ->orWhere('last_name', 'like', '%' . request()->input('search') . '%')
                ->orWhere('email', 'like', '%' . request()->input('search') . '%');
        }
        if (request()->has('manufacture_status')) {
            $query->where('manufacture_status', request()->input('manufacture_status'));
        } elseif (request()->has('status')) {
            $query->where('manufacture_status', request()->input('status'));
        }
        $manufacturers = $query->paginate(
            perPage: request()->integer('per_page', 10),
            pageName: 'page',
            page: request()->integer('page', 1),
        );

        return sendResponse(
            status: true,
            message: __('common.success'),
            data: UserResource::collection($manufacturers),
            statusCode: HttpStatus::HTTP_OK
        );
    }

    public function store(StoreManufacturerRequest $request)
    {
        $validated = $request->validated();
        $manufacturer = [];
        try {
             $manufacturer = User::create([
            'email' => $validated['email'],
            'password' => ($validated['password']),
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'role' => 'manufacturer',
            'status' => 'active',
            'manufacture_status' => 'approved',
         ]);


      $company =   $manufacturer->company()->create([
            'company_name' => $validated['company_name'],
            'company_type' => $validated['company_type'],
            'company_established' => $validated['company_established'],
            'company_size' => $validated['company_size'],
            'revenue' => $validated['revenue'],
            'country' => $validated['country'],
            'city' => $validated['city'],
            'street_address' => $validated['street_address'],
            'phone' => $validated['phone'],
            'zip_code' => $validated['zip_code'],
            'capabilities' => isset($validated['capabilities']) ? json_encode($validated['capabilities']) : null,
            'certifications' => isset($validated['certifications']) ? json_encode($validated['certifications']) : null,
            'export_markets' => isset($validated['export_markets']) ? json_encode($validated['export_markets']) : null,
            'bussiness_license' => $validated['bussiness_license'],
            'company_website' => $validated['company_website'],
            'notes' => $validated['notes'],
         ]);

        $company->autoTranslate(
            sourceData: [
                'company_name' => $validated['company_name'],
                'company_type' => $validated['company_type'],
                'company_established' => $validated['company_established'],
                'company_size' => $validated['company_size'],
                'revenue' => $validated['revenue'],
                'country' => $validated['country'],
                'city' => $validated['city'],
                'street_address' => $validated['street_address'],
                'phone' => $validated['phone'],
                'zip_code' => $validated['zip_code'],
                'notes' => $validated['notes'],
            ],
            sourceLocale: $validated['locale'] ?? null,
        );

        app(CompanySlugService::class)->syncSlug($company, $validated['company_name']);

         if($validated['industries_id']) {
            $manufacturer->load('company');
            $manufacturer->company->industries()->attach($validated['industries_id']);
         }

         if($manufacturer && $validated['send_email']) {
            // Dispatch job to send email
           SendManufacturerAccountDetailJob::dispatch($manufacturer);
        }
        } catch (\Exception $e) {
            Log::error('Failed to create manufacturer: ' . $e->getMessage());
            return sendResponse(
                status: false,
                message: __('common.error'),
                data: [],
                statusCode: HttpStatus::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        $manufacturer->load('company.industries');
        return sendResponse(
            status: true,
            message: __('common.manufacturer_created_successfully'),
            data: new UserResource($manufacturer),
            statusCode: HttpStatus::HTTP_CREATED
        );
    }



    public function show($id)
    {


        $manufacturer = User::where('role', 'manufacturer')->where('id', $id)->first();
        if(!$manufacturer) {
            return sendResponse(
                status: false,
                message: __('common.not_found'),
                data: [],
                statusCode: HttpStatus::HTTP_NOT_FOUND
            );
        }


        $manufacturer->load([
            'company.industries',
            'manufacturerReviews',
            'factoryImages',
            'preferredCurrency',
            'subscription.plan',
            'subscriptionLogs.fromPlan',
            'subscriptionLogs.toPlan',
            'additionalInformationRequests.responses',
            'additionalInformationRequests.requestedBy',
        ]);
        $manufacturer->load($this->manufacturerRelations());

        return sendResponse(
            status: true,
            message: __('common.success'),
            data: new UserResource($manufacturer),
            statusCode: HttpStatus::HTTP_OK
        );
    }

    // public function update(UpdateManufacturerRequest $request, $id)
    // {
    //     //
    // }


    public function destroy(Request $request, int $manufacturer)
    {


       $validated =  $request->validate(
            [
                'deleted_reason' => 'nullable|string|max:500',
            ]
        );
       $manufacturer = User::where('id', $manufacturer)->first();

       if(!$manufacturer) {
           return sendResponse(
               status: false,
               message: __('common.not_found'),
               data: [],
               statusCode: HttpStatus::HTTP_NOT_FOUND
           );
       }
      $manufacturer->update(
        [
            'deleted_at' => now(),
            'deleted_reason' => $validated['deleted_reason'] ?? 'Admin Deleted',
            'status' => 'deleted',
        ]
      );

      $manufacturer->load('company.industries');
       return sendResponse(
           status: true,
           message: __('common.deleted'),
           data: new UserResource($manufacturer),
           statusCode: HttpStatus::HTTP_OK
       );
    }


    public function updateStatus(Request $request, int $manufacturer)
    {



        $validated = $request->validate([
            'manufacture_status' => 'required|in:approved,rejected,pending,suspended',
            'manufacture_status_reason' => 'nullable|string|max:500',
        ]);

        $manufacturer = User::where('id', $manufacturer)->first();


        if (!$manufacturer) {
            return sendResponse(
                status: false,
                message: __('common.not_found'),
                data: [],
                statusCode: HttpStatus::HTTP_NOT_FOUND
            );
        }

        $manufactureStatus = UserManuFactureStatus::from($validated['manufacture_status']);

        if (in_array($manufactureStatus, [UserManuFactureStatus::APPROVED, UserManuFactureStatus::REJECTED, UserManuFactureStatus::PENDING], true)) {
            $manufacturer = $this->updateManufactureStatusAction->handle(
                $manufacturer,
                $manufactureStatus,
                $validated['manufacture_status_reason'] ?? null,
            );
        } else {
            $manufacturer->update([
                'manufacture_status' => $validated['manufacture_status'],
                'manufacture_status_reason' => $validated['manufacture_status_reason'] ?? null,
                'manufacture_status_at' => now(),
            ]);
        }

        $eventType = match ($validated['manufacture_status']) {
            'approved' => DashboardEventType::SupplierApproved,
            'rejected' => DashboardEventType::SupplierRejected,
            default => null,
        };

        if ($eventType !== null) {
            $this->eventTracker->track(
                eventType: $eventType,
                actor: $request->user(),
                entityType: 'supplier',
                entityId: (int) $manufacturer->id,
                counterparty: $manufacturer,
                metadata: [
                    'manufacture_status' => $validated['manufacture_status'],
                ],
            );
        }

        $manufacturer->load('company.industries');


        return sendResponse(
            status: true,
            message: __('common.updated'),
            data: new UserResource($manufacturer->fresh()),
            statusCode: HttpStatus::HTTP_OK
        );
    }

    public function suspend(Request $request, int $manufacturer)
    {
        $validated = $request->validate([
            'suspend_reason' => 'nullable|string|max:500',
        ]);

        $manufacturer = User::where('id', $manufacturer)->first();


        if (!$manufacturer) {
            return sendResponse(
                status: false,
                message: __('common.not_found'),
                data: [],
                statusCode: HttpStatus::HTTP_NOT_FOUND
            );
        }

        if($manufacturer->status != 'active') {
            return sendResponse(
                status: false,
                message: __('common.deleted_item'),
                data: [],
                statusCode: HttpStatus::HTTP_BAD_REQUEST
            );
        }

        $manufacturer->update([
            'status' => 'suspended',
            'suspend_reason' => $validated['suspend_reason'] ?? null,
            'suspended_at' => now(),
        ]);

        $this->eventTracker->track(
            eventType: DashboardEventType::SupplierSuspended,
            actor: $request->user(),
            entityType: 'supplier',
            entityId: (int) $manufacturer->id,
            counterparty: $manufacturer,
            metadata: [
                'suspend_reason' => $validated['suspend_reason'] ?? null,
            ],
        );

        $manufacturer->load('company.industries');


        return sendResponse(
            status: true,
            message: __('common.updated'),
            data: new UserResource($manufacturer->fresh()),
            statusCode: HttpStatus::HTTP_OK
        );
    }

    /**
     * @return array<int|string, mixed>
     */
    private function manufacturerRelations(): array
    {
        return [
            'company.industries',
            'manufacturerReviews',
            'factoryImages',
            'preferredCurrency',
            'additionalInformationRequests' => function ($query): void {
                $query
                    ->whereIn('status', [
                        AdditionalInformationRequestStatus::Pending->value,
                        AdditionalInformationRequestStatus::Submitted->value,
                    ])
                    ->latest()
                    ->with(['responses', 'requestedBy']);
            },
        ];
    }
}


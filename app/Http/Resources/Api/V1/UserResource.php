<?php

namespace App\Http\Resources\Api\V1;

use App\Enums\UserManuFactureStatus;
use App\Enums\UserStatus;
use App\Http\Resources\Api\V1\Admin\ReviewResource;
use App\Support\Time\TimezoneFormatter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $resource = [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'avatar' => storage_url($this->avatar),
            'avatar_url' => $this->avatar_url,
            'email' => $this->email,
            'role' => $this->role->label(),
            'status' => $this->status?->value ?? UserStatus::ACTIVE->value,
            'status_label' => $this->status?->label() ?? UserStatus::ACTIVE->label(),
            'statuses' => UserStatus::ctaOptions(),
            'agreed_to_terms' => $this->agreed_to_terms,
            'two_factor_enabled' => $this->hasEnabledTwoFactorAuthentication(),
            'deactivated_at' => TimezoneFormatter::format($this->deactivated_at),
            'deactivated_reason' => $this->deactivated_reason,
            'created_at' => TimezoneFormatter::format($this->created_at),
            'updated_at' => TimezoneFormatter::format($this->updated_at),
            'preferred_language' => $this->preferred_language ?? 'en',
            'timezone' => $this->timezone ?? config('app.timezone'),

            // Notification Preferences
            'quote_notification' => $this->quote_notification,
            'message_notification' => $this->message_notification,
            'supplier_update' => $this->supplier_update,
            'weekly_digest' => $this->weekly_digest,
            'marketing_promotion' => $this->marketing_promotion,

            'preferred_currency' => $this->whenLoaded('preferredCurrency', function (): ?array {
                if ($this->preferredCurrency === null) {
                    return null;
                }

                return [
                    'code' => $this->preferredCurrency->code,
                    'symbol' => $this->preferredCurrency->symbol,
                ];
            }),
            'login_history' => $this->whenLoaded('loginHistories', function () {
                return $this->loginHistories->map(function ($history) {
                    return [
                        'id' => $history->id,
                        'ip_address' => $history->ip_address,
                        'user_agent' => $history->user_agent,
                        'device_name' => $history->device_name,
                        'login_at' => TimezoneFormatter::format($history->login_at),
                    ];
                });
            }),
        ];

        if ($this->role->isBuyer()) {
            $resource['company'] = $this->whenLoaded('company', function (): ?array {
                if (! $this->company) {
                    return null;
                }

                return [
                    'company_name' => $this->company->company_name,
                    'country' => $this->company->country,
                    'phone' => $this->company->phone,
                ];
            });

            return $resource;
        }

        if ($this->role->isManufacturer()) {

            $normalized = UserManuFactureStatus::normalizedForManufacturer($this->manufacture_status);
            $resource['manufacture_status'] = $normalized->value;
            $resource['manufacture_status_label'] = $normalized->label();
            $resource['rejection_reason'] = $normalized->isRejected()
                ? $this->manufacture_status_reason
                : null;
            $resource['verification'] = [
                'manufacture_status' => $normalized->value,
                'manufacture_status_label' => $normalized->label(),
                'rejection_reason' => $normalized->isRejected()
                    ? $this->manufacture_status_reason
                    : null,
                'manufacture_status_at' => TimezoneFormatter::format($this->manufacture_status_at),
                'submitted_at' => TimezoneFormatter::format(
                    $this->manufacture_status_at ?? $this->created_at
                ),
            ];

            $resource['total_products'] = $this->total_products ?? 0;
            $resource['reviews'] = $this->whenLoaded('manufacturerReviews', function () {
                return ReviewResource::collection($this->manufacturerReviews);
            });
            $resource['total_reviews'] = $this->whenLoaded('manufacturerReviews', function () {
                return $this->manufacturerReviews->count();
            });

            $resource['company'] = $this->whenLoaded('company', function (): ?UserInformationResource {
                if (! $this->company) {
                    return null;
                }

                return new UserInformationResource($this->company);
            });
            $resource['factory_images'] = UserFactoryImageResource::collection($this->whenLoaded('factoryImages'));
            $resource['subscription'] = $this->whenLoaded('subscription', fn () => $this->subscription
                ? new SubscriptionResource($this->subscription)
                : null);
            $resource['subscription_logs'] = $this->whenLoaded('subscriptionLogs', function () {
                return \App\Http\Resources\Api\V1\Admin\SubscriptionLogResource::collection($this->subscriptionLogs);
            });
            $resource['additional_information_requests'] = ManufacturerAdditionalInformationRequestResource::collection(
                $this->whenLoaded('additionalInformationRequests')
            );
        }

        return $resource;
    }
}

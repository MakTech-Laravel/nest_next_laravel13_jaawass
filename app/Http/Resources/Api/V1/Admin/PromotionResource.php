<?php

namespace App\Http\Resources\Api\V1\Admin;

use App\Http\Resources\Api\V1\Admin\PlanResource;
use App\Services\Promotion\PromotionService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PromotionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $locale = $request->query('locale') ?? app()->getLocale();
        $localized = $this->resource->localizedData($locale);
        $stats = $this->resolveStats();
        $billingPeriodValue = (int) ($this->duration_months ?? PromotionService::DEFAULT_DURATION_MONTHS);
        $billingPeriodUnit = $this->billing_period_unit ?? 'month';

        return [
            'id' => $this->id,
            'promotion_title' => $localized['promotion_title'],
            'short_description' => $localized['short_description'],
            'button_text' => $localized['button_text'],
            'cta_button_text' => $localized['cta_button_text'],
            'highlight_text' => $localized['highlight_text'],
            'disclaimer_text' => $this->disclaimer_text ?? PromotionService::DEFAULT_DISCLAIMER,
            'slots' => $this->slots,
            'duration_months' => $billingPeriodValue,
            'promotional_price' => number_format((float) ($this->promotional_price ?? 0), 2, '.', ''),
            'requires_payment' => (bool) ($this->requires_payment ?? false),
            'billing_period_unit' => $billingPeriodUnit,
            'billing_period' => [
                'value' => $billingPeriodValue,
                'unit' => $billingPeriodUnit,
                'label' => app(PromotionService::class)->billingPeriodLabel($this->resource),
            ],
            'expires_at' => $this->expires_at,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'plan' => $this->whenLoaded('plan', fn () => new PlanResource($this->plan)),
            'stats' => $stats,
            'total_spots' => $stats['slots_total'],
            'approved' => $stats['accepted'],
            'spots_remaining' => $stats['spots_remaining'],
            'pending_review' => $stats['pending'],
        ];
    }

    /**
     * @return array{
     *     total_participants: int,
     *     accepted: int,
     *     pending: int,
     *     rejected: int,
     *     spots_joined: int,
     *     spots_remaining: int,
     *     slots_total: int,
     *     fill_percentage: float,
     *     is_full: bool
     * }
     */
    private function resolveStats(): array
    {
        if (
            isset($this->accepted_count)
            || isset($this->pending_count)
            || isset($this->rejected_count)
        ) {
            $accepted = (int) ($this->accepted_count ?? 0);
            $pending = (int) ($this->pending_count ?? 0);
            $rejected = (int) ($this->rejected_count ?? 0);
            $total = (int) ($this->total_participants_count ?? ($accepted + $pending + $rejected));
            $slots = (int) $this->slots;

            return [
                'total_participants' => $total,
                'accepted' => $accepted,
                'pending' => $pending,
                'rejected' => $rejected,
                'spots_joined' => $accepted,
                'spots_remaining' => max(0, $slots - $accepted),
                'slots_total' => $slots,
                'fill_percentage' => $slots > 0 ? round(($accepted / $slots) * 100, 1) : 0.0,
                'is_full' => $accepted >= $slots,
            ];
        }

        return $this->resource->enrollmentStats();
    }
}

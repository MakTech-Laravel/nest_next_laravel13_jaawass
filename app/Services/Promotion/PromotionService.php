<?php

namespace App\Services\Promotion;

use App\Enums\PromotionUserStatus;
use App\Enums\UserRole;
use App\Models\Plan;
use App\Models\Promotion;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PromotionService
{
    public const DEFAULT_SLOTS = 300;

    public const DEFAULT_DURATION_MONTHS = 6;

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
    public function enrollmentStats(Promotion $promotion): array
    {
        return $promotion->enrollmentStats();
    }

    public function deactivateAll(): void
    {
        Promotion::query()->update(['status' => false]);
    }

    public function ensureSingleActive(Promotion $promotion): void
    {
        if (! $promotion->status) {
            return;
        }

        Promotion::query()
            ->where('id', '!=', $promotion->id)
            ->update(['status' => false]);
    }

    /**
     * @return Builder<Promotion>
     */
    public function activePromotionQuery(): Builder
    {
        return Promotion::query()
            ->where('status', true)
            ->where(function (Builder $query): void {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->with([
                'translations',
                'plan.currency',
                'plan.planFeatures.feature',
            ])
            ->withCount([
                'users as accepted_count' => fn ($q) => $q->where('promotion_user.status', PromotionUserStatus::ACCEPTED->value),
                'users as pending_count' => fn ($q) => $q->where('promotion_user.status', PromotionUserStatus::PENDING->value),
                'users as rejected_count' => fn ($q) => $q->where('promotion_user.status', PromotionUserStatus::REJECTED->value),
                'users as total_participants_count',
            ])
            ->latest('id');
    }

    public function findActivePromotion(): ?Promotion
    {
        return $this->activePromotionQuery()->first();
    }

    public function reset(): Promotion
    {
        return DB::transaction(function () {
            $previous = Promotion::query()->latest('id')->first();

            $this->deactivateAll();

            $planId = Plan::query()->orderBy('id')->skip(1)->value('id')
                ?? Plan::query()->value('id');

            $promotion = Promotion::query()->create([
                'plan_id' => $planId,
                'slots' => self::DEFAULT_SLOTS,
                'duration_months' => self::DEFAULT_DURATION_MONTHS,
                'status' => true,
                'promotion_title' => $previous?->promotion_title ?? 'Founding Manufacturer',
                'short_description' => $previous?->short_description ?? 'Early Supplier Program - Get 6 months free access to the Growth plan.',
                'button_text' => $previous?->button_text ?? 'First 300 Only',
                'cta_button_text' => $previous?->cta_button_text ?? 'Apply as Founding Member',
                'highlight_text' => $previous?->highlight_text ?? 'Get full Growth plan features free for 6 months.',
                'expires_at' => null,
            ]);

            $this->syncTranslations($promotion, app()->getLocale());

            return $promotion->load('plan');
        });
    }

    public function enroll(Promotion $promotion, User $user, ?PromotionUserStatus $status = null): void
    {
        if ($promotion->users()->where('user_id', $user->id)->exists()) {
            throw new \InvalidArgumentException(__('promotion.already_enrolled'));
        }

        $status ??= PromotionUserStatus::PENDING;

        if ($status === PromotionUserStatus::ACCEPTED && $this->enrollmentStats($promotion)['is_full']) {
            throw new \InvalidArgumentException(__('promotion.full'));
        }

        $trialEndsAt = $status === PromotionUserStatus::ACCEPTED
            ? $this->trialEndsAt($promotion)
            : null;

        $promotion->users()->attach($user->id, [
            'status' => $status->value,
            'participated_at' => now(),
            'trial_ends_at' => $trialEndsAt,
        ]);
    }

    public function updateParticipantStatus(
        Promotion $promotion,
        User $user,
        PromotionUserStatus $status,
    ): void {
        if (! $promotion->users()->where('user_id', $user->id)->exists()) {
            throw new \InvalidArgumentException(__('promotion.participant_not_found'));
        }

        $currentStatus = $promotion->users()
            ->where('user_id', $user->id)
            ->first()
            ?->pivot
            ?->status;

        if (
            $status === PromotionUserStatus::ACCEPTED
            && $currentStatus !== PromotionUserStatus::ACCEPTED->value
            && $this->enrollmentStats($promotion)['is_full']
        ) {
            throw new \InvalidArgumentException(__('promotion.full'));
        }

        $trialEndsAt = $status === PromotionUserStatus::ACCEPTED
            ? $this->trialEndsAt($promotion)
            : null;

        $promotion->users()->updateExistingPivot($user->id, [
            'status' => $status->value,
            'trial_ends_at' => $trialEndsAt,
        ]);
    }

    public function syncTranslations(Promotion $promotion, ?string $locale = null): void
    {
        $locale = $locale ?? app()->getLocale();
        $sourceData = $promotion->only($promotion->translatableFields());

        $promotion->upsertTranslations([
            $locale => $sourceData,
        ]);

        $promotion->autoTranslate(
            sourceData: $sourceData,
            sourceLocale: $locale,
        );
    }

    public function assertManufacturer(User $user): void
    {
        if ($user->role !== UserRole::MANUFACTURER) {
            throw new \InvalidArgumentException(__('promotion.user_must_be_manufacturer'));
        }
    }

    private function trialEndsAt(Promotion $promotion): Carbon
    {
        return now()->addMonths((int) ($promotion->duration_months ?: self::DEFAULT_DURATION_MONTHS));
    }
}

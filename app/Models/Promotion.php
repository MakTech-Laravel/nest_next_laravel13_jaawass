<?php

namespace App\Models;

use App\Enums\PromotionUserStatus;
use App\Jobs\TranslateModelJob;
use App\Services\LocaleTranslationResolver;
use App\Traits\HasTranslations;
use Database\Factories\PromotionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['plan_id', 'slots', 'duration_months', 'promotional_price', 'requires_payment', 'billing_period_unit', 'promotion_title', 'short_description', 'button_text', 'cta_button_text', 'highlight_text', 'disclaimer_text', 'expires_at', 'status'])]
#[Hidden(['plan_id'])]
class Promotion extends Model
{
    /** @use HasFactory<PromotionFactory> */
    use HasFactory, HasTranslations;

    protected $casts = [
        'expires_at' => 'datetime',
        'status' => 'boolean',
        'slots' => 'integer',
        'duration_months' => 'integer',
        'promotional_price' => 'decimal:2',
        'requires_payment' => 'boolean',
    ];

    public function users(): BelongsToMany
    {
        return $this
            ->belongsToMany(User::class, 'promotion_user', 'promotion_id', 'user_id')
            ->withPivot('status', 'participated_at', 'trial_ends_at')
            ->withTimestamps();
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function translationModelClass(): string
    {
        return PromotionTranslation::class;
    }

    public function translatableFields(): array
    {
        return ['promotion_title', 'short_description', 'button_text', 'cta_button_text', 'highlight_text'];
    }

    public function translations(): HasMany
    {
        return $this->hasMany(PromotionTranslation::class, 'promotion_id', 'id');
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
    public function enrollmentStats(): array
    {
        $counts = $this->users()
            ->selectRaw('promotion_user.status, COUNT(*) as total')
            ->groupBy('promotion_user.status')
            ->pluck('total', 'promotion_user.status');

        $accepted = (int) ($counts[PromotionUserStatus::ACCEPTED->value] ?? 0);
        $pending = (int) ($counts[PromotionUserStatus::PENDING->value] ?? 0);
        $rejected = (int) ($counts[PromotionUserStatus::REJECTED->value] ?? 0);
        $total = $accepted + $pending + $rejected;
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

    public function localizedData(?string $locale = null, ?string $fallbackLocale = null): array
    {
        $fields = app(LocaleTranslationResolver::class)->fromRelation(
            $this,
            'translations',
            'locale',
            [
                'promotion_title' => 'promotion_title',
                'short_description' => 'short_description',
                'button_text' => 'button_text',
                'cta_button_text' => 'cta_button_text',
                'highlight_text' => 'highlight_text',
            ],
            ['promotion_title', 'short_description', 'button_text', 'cta_button_text', 'highlight_text'],
            $locale,
            $fallbackLocale
        );

        return [
            'promotion_title' => $fields['promotion_title'],
            'short_description' => $fields['short_description'],
            'button_text' => $fields['button_text'],
            'cta_button_text' => $fields['cta_button_text'],
            'highlight_text' => $fields['highlight_text'],
        ];
    }

    public function autoTranslate(array $sourceData, ?string $sourceLocale = null): void
    {
        if (config('translation.queue.enabled', true)) {
            TranslateModelJob::dispatch($this, $sourceData, $sourceLocale);
        } else {
            $this->dispatchTranslations($sourceData, $sourceLocale);
        }
    }
}

<?php

namespace App\Models;

use App\Jobs\TranslateModelJob;
use App\Services\LocaleTranslationResolver;
use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'ticket_id',
    'message',
    'user_id',
    'is_auto_reply',
])]
class TicketMessage extends Model
{
    use HasTranslations;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_auto_reply' => 'boolean',
        ];
    }

    protected function translationModelClass(): string
    {
        return TicketMessageTranslation::class;
    }

    public function translatableFields(): array
    {
        return ['message'];
    }

    /**
     * @return HasMany<TicketMessageTranslation, $this>
     */
    public function translations(): HasMany
    {
        return $this->hasMany(TicketMessageTranslation::class, 'ticket_message_id', 'id');
    }

    /**
     * @return array{message: mixed}
     */
    public function localizedData(?string $locale = null, ?string $fallbackLocale = null): array
    {
        $fields = app(LocaleTranslationResolver::class)->fromRelation(
            $this,
            'translations',
            'locale',
            ['message' => 'message'],
            ['message'],
            $locale,
            $fallbackLocale,
        );

        return [
            'message' => $fields['message'],
        ];
    }

    /**
     * @param  array<string, string>  $sourceData
     */
    public function autoTranslate(array $sourceData, ?string $sourceLocale = null): void
    {
        if (config('translation.queue.enabled', true)) {
            TranslateModelJob::dispatch($this, $sourceData, $sourceLocale);
        } else {
            $this->dispatchTranslations($sourceData, $sourceLocale);
        }
    }

    /**
     * @return BelongsTo<Ticket, $this>
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<TicketAttachment, $this>
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(TicketAttachment::class);
    }
}

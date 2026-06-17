<?php

namespace App\Services\Dashboard;

use App\Enums\DashboardEventType;
use App\Models\DashboardEvent;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class EventTrackerService
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function track(
        DashboardEventType $eventType,
        ?User $actor = null,
        ?string $entityType = null,
        ?int $entityId = null,
        ?User $counterparty = null,
        array $metadata = [],
        ?CarbonInterface $occurredAt = null,
    ): ?DashboardEvent {
        try {
            return DashboardEvent::query()->create([
                'actor_user_id' => $actor?->id,
                'counterparty_user_id' => $counterparty?->id,
                'role_context' => $this->resolveRoleContext($actor),
                'event_type' => $eventType->value,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'metadata' => $metadata,
                'occurred_at' => ($occurredAt ?? now())->toDateTimeString(),
            ]);
        } catch (\Throwable $exception) {
            Log::warning('Dashboard event tracking failed: '.$exception->getMessage());

            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function trackOnce(
        DashboardEventType $eventType,
        ?User $actor = null,
        ?string $entityType = null,
        ?int $entityId = null,
        ?User $counterparty = null,
        array $metadata = [],
        ?CarbonInterface $occurredAt = null,
    ): ?DashboardEvent {
        $timestamp = Carbon::parse($occurredAt ?? now())->toDateTimeString();

        try {
            return DashboardEvent::query()->firstOrCreate(
                [
                    'event_type' => $eventType->value,
                    'actor_user_id' => $actor?->id,
                    'counterparty_user_id' => $counterparty?->id,
                    'entity_type' => $entityType,
                    'entity_id' => $entityId,
                    'occurred_at' => $timestamp,
                ],
                [
                    'role_context' => $this->resolveRoleContext($actor),
                    'metadata' => $metadata,
                ]
            );
        } catch (\Throwable $exception) {
            Log::warning('Dashboard event trackOnce failed: '.$exception->getMessage());

            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function trackOnceWithinWindow(
        DashboardEventType $eventType,
        ?User $actor = null,
        ?string $entityType = null,
        ?int $entityId = null,
        ?User $counterparty = null,
        array $metadata = [],
        int $windowMinutes = 30,
    ): ?DashboardEvent {
        $threshold = now()->subMinutes($windowMinutes);

        $existing = DashboardEvent::query()
            ->where('event_type', $eventType->value)
            ->where('actor_user_id', $actor?->id)
            ->where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->where('occurred_at', '>=', $threshold)
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        return $this->track(
            eventType: $eventType,
            actor: $actor,
            entityType: $entityType,
            entityId: $entityId,
            counterparty: $counterparty,
            metadata: $metadata,
        );
    }

    private function resolveRoleContext(?User $actor): string
    {
        if ($actor === null) {
            return 'system';
        }

        $role = $actor->role;

        if ($role instanceof \BackedEnum) {
            return (string) $role->value;
        }

        return (string) $role;
    }
}

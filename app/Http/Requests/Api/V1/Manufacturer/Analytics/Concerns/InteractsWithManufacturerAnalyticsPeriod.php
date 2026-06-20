<?php

namespace App\Http\Requests\Api\V1\Manufacturer\Analytics\Concerns;

use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

trait InteractsWithManufacturerAnalyticsPeriod
{
    /**
     * @return array<string, mixed>
     */
    protected function periodRules(): array
    {
        return [
            'period' => ['sometimes', 'string', Rule::in([
                'last_7_days',
                'last_30_days',
                'last_90_days',
                'last_365_days',
                'custom',
            ])],
            'date_from' => ['sometimes', 'nullable', 'date'],
            'date_to' => ['sometimes', 'nullable', 'date', 'after_or_equal:date_from'],
        ];
    }

    public function period(): string
    {
        return $this->input('period', 'last_30_days');
    }

    /**
     * @return array{
     *     current_start: Carbon,
     *     current_end: Carbon,
     *     previous_start: Carbon,
     *     previous_end: Carbon,
     *     period: string
     * }
     */
    public function resolvedPeriodRange(): array
    {
        $period = $this->period();

        if ($period === 'custom' && $this->filled('date_from') && $this->filled('date_to')) {
            $currentStart = Carbon::parse($this->input('date_from'))->startOfDay();
            $currentEnd = Carbon::parse($this->input('date_to'))->endOfDay();
            $days = max(1, $currentStart->diffInDays($currentEnd) + 1);

            return [
                'current_start' => $currentStart,
                'current_end' => $currentEnd,
                'previous_start' => $currentStart->copy()->subDays($days)->startOfDay(),
                'previous_end' => $currentStart->copy()->subDay()->endOfDay(),
                'period' => $period,
            ];
        }

        $now = now();

        return match ($period) {
            'last_7_days' => [
                'current_start' => $now->copy()->subDays(6)->startOfDay(),
                'current_end' => $now->copy()->endOfDay(),
                'previous_start' => $now->copy()->subDays(13)->startOfDay(),
                'previous_end' => $now->copy()->subDays(7)->endOfDay(),
                'period' => $period,
            ],
            'last_90_days' => [
                'current_start' => $now->copy()->subDays(89)->startOfDay(),
                'current_end' => $now->copy()->endOfDay(),
                'previous_start' => $now->copy()->subDays(179)->startOfDay(),
                'previous_end' => $now->copy()->subDays(90)->endOfDay(),
                'period' => $period,
            ],
            'last_365_days' => [
                'current_start' => $now->copy()->subDays(364)->startOfDay(),
                'current_end' => $now->copy()->endOfDay(),
                'previous_start' => $now->copy()->subDays(729)->startOfDay(),
                'previous_end' => $now->copy()->subDays(365)->endOfDay(),
                'period' => $period,
            ],
            default => [
                'current_start' => $now->copy()->subDays(29)->startOfDay(),
                'current_end' => $now->copy()->endOfDay(),
                'previous_start' => $now->copy()->subDays(59)->startOfDay(),
                'previous_end' => $now->copy()->subDays(30)->endOfDay(),
                'period' => 'last_30_days',
            ],
        };
    }
}

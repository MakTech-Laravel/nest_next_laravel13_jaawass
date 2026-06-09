<?php

namespace App\Services;

use App\Enums\QuickFilterType;
use App\Models\QuickFilterOption;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class QuickFilterService
{
    /**
     * @return array{filter_types: int, total_options: int, enabled: int, disabled: int}
     */
    public function getCounts(): array
    {
        $total = QuickFilterOption::query()->count();
        $enabled = QuickFilterOption::query()->where('is_enabled', true)->count();

        return [
            'filter_types' => QuickFilterType::definedTypeCount(),
            'total_options' => $total,
            'enabled' => $enabled,
            'disabled' => $total - $enabled,
        ];
    }

    /**
     * @return Collection<int, QuickFilterOption>
     */
    public function listByType(QuickFilterType $type): Collection
    {
        return QuickFilterOption::query()
            ->forType($type)
            ->ordered()
            ->get();
    }

    /**
     * @return Collection<int, QuickFilterOption>
     */
    public function listEnabledByType(QuickFilterType $type): Collection
    {
        return QuickFilterOption::query()
            ->forType($type)
            ->enabled()
            ->ordered()
            ->get();
    }

    public function create(QuickFilterType $type, string $displayLabel, ?string $value, bool $isEnabled = true): QuickFilterOption
    {
        $base = ($value !== null && trim($value) !== '')
            ? Str::slug($value)
            : Str::slug($displayLabel);

        $resolved = $this->uniqueValueForType($type, $base, ignoreId: null);

        return DB::transaction(function () use ($type, $displayLabel, $resolved, $isEnabled): QuickFilterOption {
            $nextOrder = (int) QuickFilterOption::query()->forType($type)->lockForUpdate()->max('sort_order') + 1;

            return QuickFilterOption::query()->create([
                'type' => $type,
                'display_label' => $displayLabel,
                'value' => $resolved,
                'is_enabled' => $isEnabled,
                'sort_order' => $nextOrder,
            ]);
        });
    }

    /**
     * @param  array{display_label?: string, value?: string|null, is_enabled?: bool}  $data
     */
    public function update(QuickFilterOption $option, array $data): QuickFilterOption
    {
        if (array_key_exists('display_label', $data)) {
            $option->display_label = $data['display_label'];
        }

        if (array_key_exists('value', $data)) {
            $raw = $data['value'];
            $slug = ($raw !== null && trim((string) $raw) !== '')
                ? Str::slug((string) $raw)
                : Str::slug($option->display_label);
            $option->value = $this->uniqueValueForType($option->type, $slug, ignoreId: $option->id);
        }

        if (array_key_exists('is_enabled', $data)) {
            $option->is_enabled = (bool) $data['is_enabled'];
        }

        $option->save();

        return $option->fresh();
    }

    public function delete(QuickFilterOption $option): void
    {
        $option->delete();
    }

    public function toggle(QuickFilterOption $option, ?bool $isEnabled = null): QuickFilterOption
    {
        if ($isEnabled !== null) {
            $option->is_enabled = $isEnabled;
        } else {
            $option->is_enabled = ! $option->is_enabled;
        }
        $option->save();

        return $option->fresh();
    }

    public function moveSort(QuickFilterOption $option, string $direction): void
    {
        DB::transaction(function () use ($option, $direction): void {
            $locked = QuickFilterOption::query()->whereKey($option->id)->lockForUpdate()->firstOrFail();
            $type = $locked->type;

            if ($direction === 'up') {
                $neighbor = QuickFilterOption::query()
                    ->forType($type)
                    ->where(function ($q) use ($locked): void {
                        $q->where('sort_order', '<', $locked->sort_order)
                            ->orWhere(function ($q2) use ($locked): void {
                                $q2->where('sort_order', '=', $locked->sort_order)
                                    ->where('id', '<', $locked->id);
                            });
                    })
                    ->orderByDesc('sort_order')
                    ->orderByDesc('id')
                    ->lockForUpdate()
                    ->first();
            } else {
                $neighbor = QuickFilterOption::query()
                    ->forType($type)
                    ->where(function ($q) use ($locked): void {
                        $q->where('sort_order', '>', $locked->sort_order)
                            ->orWhere(function ($q2) use ($locked): void {
                                $q2->where('sort_order', '=', $locked->sort_order)
                                    ->where('id', '>', $locked->id);
                            });
                    })
                    ->orderBy('sort_order')
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->first();
            }

            if ($neighbor === null) {
                return;
            }

            $a = $locked->sort_order;
            $b = $neighbor->sort_order;
            $neighbor->update(['sort_order' => $a]);
            $locked->update(['sort_order' => $b]);
        });
    }

    private function uniqueValueForType(QuickFilterType $type, string $baseSlug, ?int $ignoreId): string
    {
        $slug = $baseSlug !== '' ? $baseSlug : 'option';
        $candidate = $slug;
        $i = 2;

        while (QuickFilterOption::query()
            ->forType($type)
            ->where('value', $candidate)
            ->when($ignoreId !== null, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists()) {
            $candidate = $slug.'-'.$i;
            $i++;
        }

        return $candidate;
    }
}

<?php

namespace App\Http\Requests\Api\V1\Buyer\Order;

use App\Http\Requests\Api\V1\Concerns\InteractsWithOrderIndexFilters;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexOrderRequest extends FormRequest
{
    use InteractsWithOrderIndexFilters;

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            ...$this->orderIndexFilterRules(),
            'manufacturer_id' => ['sometimes', 'integer', Rule::exists('users', 'id')],
        ];
    }

    public function manufacturerId(): ?int
    {
        return $this->filled('manufacturer_id') ? $this->integer('manufacturer_id') : null;
    }
}

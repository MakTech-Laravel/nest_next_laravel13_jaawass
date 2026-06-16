<?php

namespace App\Http\Requests\Api\V1\Manufacturer\Order;

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
            'buyer_id' => ['sometimes', 'integer', Rule::exists('users', 'id')],
        ];
    }

    public function buyerId(): ?int
    {
        return $this->filled('buyer_id') ? $this->integer('buyer_id') : null;
    }
}

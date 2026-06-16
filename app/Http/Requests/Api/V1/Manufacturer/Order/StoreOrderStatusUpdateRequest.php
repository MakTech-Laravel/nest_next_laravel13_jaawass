<?php

namespace App\Http\Requests\Api\V1\Manufacturer\Order;

use App\Http\Requests\Api\V1\Concerns\ValidatesOrderStatusUpdate;
use App\Models\Order;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreOrderStatusUpdateRequest extends FormRequest
{
    use ValidatesOrderStatusUpdate;

    public function authorize(): bool
    {
        /** @var Order|null $order */
        $order = $this->route('order');

        if ($order === null || $this->user() === null) {
            return false;
        }

        return (int) $order->manufacturer_id === (int) $this->user()->id;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->orderStatusUpdateRules();
    }

    public function withValidator(Validator $validator): void
    {
        $this->validateOrderStatusUpdateContent($validator);
    }
}

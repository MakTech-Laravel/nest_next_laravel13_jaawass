<?php

namespace App\Http\Requests\Api\V1\Manufacturer\Order;

use App\Models\Product;
use App\Models\RfqSubmission;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderRequest extends FormRequest
{
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
            'buyer_id' => ['required', 'integer', Rule::exists('users', 'id')],
            'product_id' => ['required', 'integer', Rule::exists('products', 'id')],
            'title' => ['required', 'string', 'max:255'],
            'quantity' => ['required', 'integer', 'min:1'],
            'quantity_unit' => ['sometimes', 'nullable', 'string', 'max:64'],
            'total_amount' => ['required', 'numeric', 'min:0'],
            'currency_code' => ['sometimes', 'nullable', 'string', 'size:3'],
            'estimated_delivery_at' => ['required', 'date'],
            'production_lead' => ['sometimes', 'nullable', 'string', 'max:128'],
            'payment_terms' => ['sometimes', 'nullable', 'string', 'max:255'],
            'shipping_terms' => ['sometimes', 'nullable', 'string', 'max:128'],
            'destination' => ['sometimes', 'nullable', 'string', 'max:255'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'locale' => ['sometimes', 'nullable', 'string', 'max:10'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $manufacturerId = (int) $this->user()->id;
            $productId = $this->integer('product_id');
            $buyerId = $this->integer('buyer_id');

            $product = Product::query()->find($productId);

            if ($product !== null && (int) $product->user_id !== $manufacturerId) {
                $validator->errors()->add(
                    'product_id',
                    __('api.manufacturer_order_product_not_owned'),
                );
            }

            $hasRfq = RfqSubmission::query()
                ->where('product_id', $productId)
                ->where('buyer_id', $buyerId)
                ->where('manufacturer_id', $manufacturerId)
                ->exists();

            if (! $hasRfq) {
                $validator->errors()->add(
                    'buyer_id',
                    __('api.manufacturer_order_buyer_not_connected'),
                );
            }
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function orderAttributes(int $manufacturerId): array
    {
        $validated = $this->validated();

        return [
            'user_id' => $manufacturerId,
            'buyer_id' => $validated['buyer_id'],
            'manufacturer_id' => $manufacturerId,
            'product_id' => $validated['product_id'],
            'title' => $validated['title'],
            'quantity' => $validated['quantity'],
            'quantity_unit' => $validated['quantity_unit'] ?? 'pieces',
            'total_amount' => $validated['total_amount'],
            'currency_code' => strtoupper($validated['currency_code'] ?? 'USD'),
            'estimated_delivery_at' => $validated['estimated_delivery_at'],
            'production_lead' => $validated['production_lead'] ?? null,
            'payment_terms' => $validated['payment_terms'] ?? null,
            'shipping_terms' => $validated['shipping_terms'] ?? null,
            'destination' => $validated['destination'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ];
    }

    /**
     * @return array<string, string>
     */
    public function translationSourceData(): array
    {
        $validated = $this->validated();

        return array_filter([
            'title' => $validated['title'],
            'notes' => $validated['notes'] ?? null,
        ], fn (mixed $value): bool => $value !== null && $value !== '');
    }

    public function sourceLocale(): string
    {
        return $this->input('locale') ?? app()->getLocale();
    }
}

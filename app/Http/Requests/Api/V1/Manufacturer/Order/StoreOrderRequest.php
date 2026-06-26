<?php

namespace App\Http\Requests\Api\V1\Manufacturer\Order;

use App\Models\Product;
use App\Models\RfqSubmission;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\Validator as IlluminateValidator;

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
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.product_id' => ['required', 'integer', Rule::exists('products', 'id')],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.quantity_unit' => ['sometimes', 'nullable', 'string', 'max:64'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.notes' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'title' => ['required', 'string', 'max:255'],
            'total_amount' => ['required', 'numeric', 'min:0'],
            'currency_code' => ['sometimes', 'nullable', 'string', 'size:3'],
            'estimated_delivery_at' => ['required', 'date', 'after_or_equal:today'],
            'production_lead' => ['sometimes', 'nullable', 'string', 'max:128'],
            'payment_terms' => ['sometimes', 'nullable', 'string', 'max:255'],
            'shipping_terms' => ['sometimes', 'nullable', 'string', 'max:128'],
            'destination' => ['sometimes', 'nullable', 'string', 'max:255'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'locale' => ['sometimes', 'nullable', 'string', 'max:10'],
            'attachments' => ['sometimes', 'array', 'max:5'],
            'attachments.*' => [
                'file',
                File::types(['pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'jpg', 'jpeg', 'png', 'webp'])
                    ->max(51200),
            ],
            'product_id' => ['prohibited'],
            'quantity' => ['prohibited'],
            'quantity_unit' => ['prohibited'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (IlluminateValidator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $manufacturerId = (int) $this->user()->id;
            $buyerId = $this->integer('buyer_id');
            $items = $this->normalizedItems();

            foreach ($items as $index => $item) {
                $productId = (int) $item['product_id'];
                $product = Product::query()->find($productId);

                if ($product !== null && (int) $product->user_id !== $manufacturerId) {
                    $validator->errors()->add(
                        "items.{$index}.product_id",
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

                    break;
                }
            }

            $computedTotal = $this->computedTotalAmount();
            $submittedTotal = round((float) $this->input('total_amount'), 2);

            if (abs($computedTotal - $submittedTotal) > 0.01) {
                $validator->errors()->add(
                    'total_amount',
                    __('api.manufacturer_order_total_amount_mismatch'),
                );
            }
        });
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function normalizedItems(): array
    {
        $items = $this->input('items', []);

        return array_map(function (array $item): array {
            $quantity = (int) $item['quantity'];
            $unitPrice = round((float) $item['unit_price'], 4);

            return [
                'product_id' => (int) $item['product_id'],
                'quantity' => $quantity,
                'quantity_unit' => $item['quantity_unit'] ?? 'pieces',
                'unit_price' => $unitPrice,
                'line_total' => round($quantity * $unitPrice, 2),
                'notes' => $item['notes'] ?? null,
            ];
        }, $items);
    }

    public function computedTotalAmount(): float
    {
        $total = 0.0;

        foreach ($this->normalizedItems() as $item) {
            $total += $item['line_total'];
        }

        return round($total, 2);
    }

    /**
     * @return array<string, mixed>
     */
    public function orderAttributes(int $manufacturerId): array
    {
        $validated = $this->validated();
        $firstItem = $this->normalizedItems()[0];

        return [
            'user_id' => $manufacturerId,
            'buyer_id' => $validated['buyer_id'],
            'manufacturer_id' => $manufacturerId,
            'product_id' => $firstItem['product_id'],
            'title' => $validated['title'],
            'quantity' => $firstItem['quantity'],
            'quantity_unit' => $firstItem['quantity_unit'],
            'total_amount' => $this->computedTotalAmount(),
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

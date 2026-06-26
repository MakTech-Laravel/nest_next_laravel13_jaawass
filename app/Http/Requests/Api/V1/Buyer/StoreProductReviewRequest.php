<?php

namespace App\Http\Requests\Api\V1\Buyer;

use App\Enums\OrderStatus;
use App\Enums\ReviewStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreProductReviewRequest extends FormRequest
{
    private ?Order $eligibleOrder = null;

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'order_id' => ['required', 'integer', Rule::exists('orders', 'id')],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'comment' => ['required', 'string', 'max:5000'],
            'locale' => ['sometimes', 'string', 'max:10'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty() || $this->user() === null) {
                return;
            }

            /** @var Product|null $product */
            $product = $this->route('product');
            if (! $product instanceof Product) {
                return;
            }

            $order = Order::query()
                ->whereKey($this->integer('order_id'))
                ->where('buyer_id', (int) $this->user()->id)
                ->where('status', OrderStatus::Completed->value)
                ->where(function ($query) use ($product): void {
                    $query
                        ->where('product_id', (int) $product->id)
                        ->orWhereHas('items', function ($itemQuery) use ($product): void {
                            $itemQuery->where('product_id', (int) $product->id);
                        });
                })
                ->first();

            if ($order === null) {
                $validator->errors()->add('order_id', __('api.review_order_not_eligible'));

                return;
            }

            $alreadyReviewed = Review::query()
                ->where('reviewer_id', (int) $this->user()->id)
                ->where('order_id', (int) $order->id)
                ->exists();

            if ($alreadyReviewed) {
                $validator->errors()->add('order_id', __('api.review_already_exists_for_order'));

                return;
            }

            $this->eligibleOrder = $order;
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function reviewAttributes(Product $product): array
    {
        $validated = $this->validated();

        return [
            'user_id' => (int) ($this->eligibleOrder?->manufacturer_id ?? $product->user_id),
            'product_id' => (int) $product->id,
            'order_id' => (int) $validated['order_id'],
            'reviewer_id' => (int) $this->user()->id,
            'rating' => (int) $validated['rating'],
            'title' => $validated['title'] ?? null,
            'comment' => $validated['comment'],
            'status' => ReviewStatus::PENDING->value,
        ];
    }
}

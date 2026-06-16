<?php

namespace App\Filters\Api\V1\Manufacturer\Order;

use App\Http\Requests\Api\V1\Manufacturer\Order\IndexOrderRequest;
use Illuminate\Database\Eloquent\Builder;

class OrderFilter
{
    public function __construct(private Builder $query) {}

    public static function apply(Builder $query, IndexOrderRequest $request, int $manufacturerId): Builder
    {
        $query->where('manufacturer_id', $manufacturerId);

        return (new self($query))
            ->withBuyerFilter($request->buyerId())
            ->withProductFilter($request->productId())
            ->withSearch($request->searchTerm())
            ->query;
    }

    private function withBuyerFilter(?int $buyerId): self
    {
        if ($buyerId !== null) {
            $this->query->where('buyer_id', $buyerId);
        }

        return $this;
    }

    private function withProductFilter(?int $productId): self
    {
        if ($productId !== null) {
            $this->query->where('product_id', $productId);
        }

        return $this;
    }

    private function withSearch(?string $searchTerm): self
    {
        if ($searchTerm === null) {
            return $this;
        }

        $this->query->where(function (Builder $builder) use ($searchTerm): void {
            $builder
                ->where('title', 'like', "%{$searchTerm}%")
                ->orWhere('destination', 'like', "%{$searchTerm}%")
                ->orWhereHas('buyer', function (Builder $buyerQuery) use ($searchTerm): void {
                    $buyerQuery
                        ->where('first_name', 'like', "%{$searchTerm}%")
                        ->orWhere('last_name', 'like', "%{$searchTerm}%")
                        ->orWhere('email', 'like', "%{$searchTerm}%");
                })
                ->orWhereHas('product', function (Builder $productQuery) use ($searchTerm): void {
                    $productQuery->where('name', 'like', "%{$searchTerm}%");
                });
        });

        return $this;
    }
}

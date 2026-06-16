<?php

namespace App\Filters\Api\V1\Order;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Builder;

class OrderFilter
{
    public function __construct(private Builder $query) {}

    public static function apply(
        Builder $query,
        ?int $buyerId = null,
        ?int $manufacturerId = null,
        ?int $productId = null,
        ?string $status = null,
        ?string $searchTerm = null,
    ): Builder {
        if ($buyerId !== null) {
            $query->where('buyer_id', $buyerId);
        }

        if ($manufacturerId !== null) {
            $query->where('manufacturer_id', $manufacturerId);
        }

        return (new self($query))
            ->withProductFilter($productId)
            ->withStatusFilter($status)
            ->withSearch($searchTerm)
            ->query;
    }

    private function withProductFilter(?int $productId): self
    {
        if ($productId !== null) {
            $this->query->where('product_id', $productId);
        }

        return $this;
    }

    private function withStatusFilter(?string $status): self
    {
        if ($status !== null && in_array($status, OrderStatus::values(), true)) {
            $this->query->where('status', $status);
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
                        ->orWhere('email', 'like', "%{$searchTerm}%")
                        ->orWhereHas('company', function (Builder $companyQuery) use ($searchTerm): void {
                            $companyQuery->where('company_name', 'like', "%{$searchTerm}%");
                        });
                })
                ->orWhereHas('manufacturer', function (Builder $manufacturerQuery) use ($searchTerm): void {
                    $manufacturerQuery
                        ->where('first_name', 'like', "%{$searchTerm}%")
                        ->orWhere('last_name', 'like', "%{$searchTerm}%")
                        ->orWhere('email', 'like', "%{$searchTerm}%")
                        ->orWhereHas('company', function (Builder $companyQuery) use ($searchTerm): void {
                            $companyQuery->where('company_name', 'like', "%{$searchTerm}%");
                        });
                })
                ->orWhereHas('product', function (Builder $productQuery) use ($searchTerm): void {
                    $productQuery
                        ->where('name', 'like', "%{$searchTerm}%")
                        ->orWhere('slug', 'like', "%{$searchTerm}%");
                });
        });

        return $this;
    }
}

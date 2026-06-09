<?php

namespace App\Services\Buyer;

use App\Enums\UserRole;
use App\Models\CompareSupplier;
use App\Models\SaveSupplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class BuyerSupplierListService
{
    public function save(User $buyer, int $supplierId): SaveSupplier
    {
        $supplier = $this->resolveSupplierForBuyer($buyer, $supplierId);

        return SaveSupplier::query()->firstOrCreate([
            'user_id' => $buyer->id,
            'supplier_id' => $supplier->id,
        ]);
    }

    public function unsave(User $buyer, int $supplierId): void
    {
        $deleted = SaveSupplier::query()
            ->where('user_id', $buyer->id)
            ->where('supplier_id', $supplierId)
            ->delete();

        if ($deleted === 0) {
            throw ValidationException::withMessages([
                'supplier_id' => [__('api.saved_supplier_not_found')],
            ]);
        }
    }

    public function addToCompare(User $buyer, int $supplierId): CompareSupplier
    {
        $supplier = $this->resolveSupplierForBuyer($buyer, $supplierId);

        return CompareSupplier::query()->firstOrCreate([
            'user_id' => $buyer->id,
            'supplier_id' => $supplier->id,
        ]);
    }

    public function removeFromCompare(User $buyer, int $supplierId): void
    {
        $deleted = CompareSupplier::query()
            ->where('user_id', $buyer->id)
            ->where('supplier_id', $supplierId)
            ->delete();

        if ($deleted === 0) {
            throw ValidationException::withMessages([
                'supplier_id' => [__('api.compare_supplier_not_found')],
            ]);
        }
    }

    /**
     * @return Collection<int, User>
     */
    public function savedSuppliers(User $buyer): Collection
    {
        return $buyer
            ->savedSuppliers()
            ->with($this->supplierRelations())
            ->latest('save_suppliers.created_at')
            ->get();
    }

    /**
     * @return Collection<int, User>
     */
    public function compareSuppliers(User $buyer): Collection
    {
        return $buyer
            ->compareSuppliers()
            ->with($this->supplierRelations())
            ->latest('compare_suppliers.created_at')
            ->get();
    }

    private function resolveSupplierForBuyer(User $buyer, int $supplierId): User
    {
        $supplier = User::query()
            ->where('role', UserRole::MANUFACTURER->value)
            ->find($supplierId);

        if ($supplier === null) {
            throw ValidationException::withMessages([
                'supplier_id' => [__('api.supplier_not_found')],
            ]);
        }

        if ((int) $supplier->id === (int) $buyer->id) {
            throw ValidationException::withMessages([
                'supplier_id' => [__('api.buyer_own_supplier_not_allowed')],
            ]);
        }

        return $supplier;
    }

    /**
     * @return array<int, string>
     */
    private function supplierRelations(): array
    {
        return [
            'company',
            'company.translations',
        ];
    }
}

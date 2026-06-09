<?php

namespace App\Enums;

enum UserRole: string
{
    case BUYER = 'buyer';
    case MANUFACTURER = 'manufacturer';
    case ADMIN = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::BUYER => 'Buyer',
            self::MANUFACTURER => 'Manufacturer',
            self::ADMIN => 'Admin',
        };
    }

    public function isAdmin(): bool
    {
        return $this === self::ADMIN;
    }

    public function isBuyer(): bool
    {
        return $this === self::BUYER;
    }

    public function isManufacturer(): bool
    {
        return $this === self::MANUFACTURER;
    }

    public function options(): array
    {
        return [
            // self::ADMIN->value => self::ADMIN->label(),
            self::BUYER->value => self::BUYER->label(),
            self::MANUFACTURER->value => self::MANUFACTURER->label(),
        ];
    }
}

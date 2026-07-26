<?php

namespace App\Enum;

enum OrderStatus: string
{
    case Preparing = 'EP';
    case Shipped = 'EX';
    case Delivered = 'LV';

    /** Libellé affichable côté client */
    public function label(): string
    {
        return match ($this) {
            self::Preparing => 'En préparation',
            self::Shipped => 'Expédiée',
            self::Delivered => 'Livrée',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Preparing => 'badge-preparing',
            self::Shipped => 'badge-shipped',
            self::Delivered => 'badge-delivered',
        };
    }

    public function next(): ?self
    {
        return match ($this) {
            self::Preparing => self::Shipped,
            self::Shipped => self::Delivered,
            self::Delivered => null,
        };
    }
}

<?php

namespace App\Service;

use App\Entity\Product;
use App\Entity\User;
use App\Repository\CartItemRepository;

class StockService
{
    public function __construct(
        private CartItemRepository $cartItemRepository
    )
    {
    }

    public function getAvailable(Product $product, ?User $currentUser = null): int
    {
        $reserved = $this->cartItemRepository->getActiveReservedQuantity($product, $currentUser);

        return max(0, $product->getStock() - $reserved);
    }

    /**
     * @param Product[] $products
     * @return array<int, int>  [productId => disponible]
     */
    public function getAvailableMap(array $products): array
    {
        $reservations = $this->cartItemRepository->getActiveReservationsByProduct();

        $map = [];
        foreach ($products as $product) {
            $reserved = $reservations[$product->getId()] ?? 0;
            $map[$product->getId()] = max(0, $product->getStock() - $reserved);
        }

        return $map;
    }
}

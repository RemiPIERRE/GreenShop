<?php

namespace App\Repository;

use App\Entity\CartItem;
use App\Entity\Product;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CartItem>
 */
class CartItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CartItem::class);
    }

    /**
     * @return array<int, int>  [productId => quantité réservée]
     */
    public function getActiveReservationsByProduct(): array
    {
        $rows = $this->createQueryBuilder('c')
            ->select('IDENTITY(c.product) AS productId', 'SUM(c.quantity) AS qty')
            ->where('c.expiresAt > :now')
            ->setParameter('now', new \DateTimeImmutable())
            ->groupBy('c.product')
            ->getQuery()
            ->getResult();

        $map = [];
        foreach ($rows as $row) {
            $map[(int)$row['productId']] = (int)$row['qty'];
        }

        return $map;
    }

    public function getActiveReservedQuantity(Product $product, ?User $excluding = null): int
    {
        $qb = $this->createQueryBuilder('c')
            ->select('COALESCE(SUM(c.quantity), 0)')
            ->where('c.product = :product')
            ->andWhere('c.expiresAt > :now')
            ->setParameter('product', $product)
            ->setParameter('now', new \DateTimeImmutable());

        if ($excluding !== null) {
            $qb->andWhere('c.user != :user')
                ->setParameter('user', $excluding);
        }

        return (int)$qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @return CartItem[]
     */
    public function findActiveForUser(User $user): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.user = :user')
            ->andWhere('c.expiresAt > :now')
            ->setParameter('user', $user)
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('c.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findOneActiveForUserAndProduct(User $user, Product $product): ?CartItem
    {
        return $this->createQueryBuilder('c')
            ->where('c.user = :user')
            ->andWhere('c.product = :product')
            ->andWhere('c.expiresAt > :now')
            ->setParameter('user', $user)
            ->setParameter('product', $product)
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findCartExpiry(User $user): ?\DateTimeImmutable
    {
        $item = $this->createQueryBuilder('c')
            ->where('c.user = :user')
            ->andWhere('c.expiresAt > :now')
            ->setParameter('user', $user)
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $item?->getExpiresAt();
    }
}

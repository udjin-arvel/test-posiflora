<?php

namespace App\Repository;

use App\Entity\CustomerOrder;
use App\Entity\OrderStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CustomerOrder>
 */
class CustomerOrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CustomerOrder::class);
    }

    public function findOneByShopAndExternalId(string $shopId, string $externalId): ?CustomerOrder
    {
        return $this->findOneBy(['shopId' => $shopId, 'externalId' => $externalId]);
    }

    /** @return list<CustomerOrder> */
    public function listForShop(string $shopId, ?OrderStatus $status): array
    {
        $qb = $this->createQueryBuilder('o')
            ->leftJoin('o.items', 'i')
            ->addSelect('i')
            ->where('o.shopId = :shop')
            ->setParameter('shop', $shopId)
            ->addOrderBy('CASE WHEN o.marketplaceCreatedAt IS NULL THEN 1 ELSE 0 END', 'ASC')
            ->addOrderBy('o.marketplaceCreatedAt', 'DESC')
            ->addOrderBy('o.externalId', 'ASC');

        if ($status !== null) {
            $qb->andWhere('o.status = :status')->setParameter('status', $status);
        }

        return $qb->getQuery()->getResult();
    }

    /** @return array<string, int> */
    public function countByStatusForShop(string $shopId): array
    {
        $rows = $this->createQueryBuilder('o')
            ->select('o.status AS status', 'COUNT(o.id) AS cnt')
            ->where('o.shopId = :shop')
            ->setParameter('shop', $shopId)
            ->groupBy('o.status')
            ->getQuery()
            ->getArrayResult();

        $counts = [];
        foreach (OrderStatus::cases() as $case) {
            $counts[$case->value] = 0;
        }
        $all = 0;
        foreach ($rows as $row) {
            $value = $row['status'] instanceof OrderStatus ? $row['status']->value : (string) $row['status'];
            $counts[$value] = (int) $row['cnt'];
            $all += (int) $row['cnt'];
        }
        $counts['all'] = $all;

        return $counts;
    }

    public function countReviewForShop(string $shopId): int
    {
        return (int) $this->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->where('o.shopId = :shop')
            ->andWhere('o.needsReview = true')
            ->setParameter('shop', $shopId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /** @return list<CustomerOrder> */
    public function findAllForShop(string $shopId): array
    {
        return $this->findBy(['shopId' => $shopId]);
    }
}

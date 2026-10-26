<?php

namespace App\Repository;

use App\Entity\DeliveryTariff;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DeliveryTariff>
 */
class DeliveryTariffRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DeliveryTariff::class);
    }

    public function findByCode(string $code): ?DeliveryTariff
    {
        return $this->find($code);
    }
}

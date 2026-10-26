<?php

namespace App\Service\Order;

use App\Repository\CustomerOrderRepository;
use Doctrine\ORM\EntityManagerInterface;

final class DemoResetService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly CustomerOrderRepository $orders,
        private readonly string $shopId,
    ) {
    }

    public function reset(): void
    {
        $this->em->wrapInTransaction(function (): void {
            foreach ($this->orders->findAllForShop($this->shopId) as $order) {
                $this->em->remove($order);
            }
            $this->em->flush();
        });
    }
}

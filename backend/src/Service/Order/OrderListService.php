<?php

namespace App\Service\Order;

use App\Entity\OrderStatus;
use App\Repository\CustomerOrderRepository;

final class OrderListService
{
    public function __construct(
        private readonly CustomerOrderRepository $orders,
        private readonly string $shopId,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function list(?string $statusRaw): array
    {
        $status = null;
        if ($statusRaw !== null && $statusRaw !== '') {
            $status = OrderStatus::tryFrom($statusRaw);
            if ($status === null) {
                throw new \InvalidArgumentException('Неизвестный статус');
            }
        }

        $entities = $this->orders->listForShop($this->shopId, $status);
        $counts = $this->orders->countByStatusForShop($this->shopId);

        return [
            'items' => array_map(static fn ($o) => OrderPresenter::toArray($o), $entities),
            'counts' => $counts,
            'reviewCount' => $this->orders->countReviewForShop($this->shopId),
        ];
    }
}

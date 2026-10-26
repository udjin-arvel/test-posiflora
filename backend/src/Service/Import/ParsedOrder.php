<?php

namespace App\Service\Import;

use App\Entity\DeliveryType;
use App\Entity\OrderStatus;

final readonly class ParsedOrder
{
    /**
     * @param list<array{sku: ?string, name: string, qty: int, priceKop: int}> $items
     */
    public function __construct(
        public string $externalId,
        public OrderStatus $status,
        public ?\DateTimeImmutable $marketplaceCreatedAt,
        public string $customerName,
        public string $customerPhone,
        public DeliveryType $deliveryType,
        public ?string $district,
        public ?string $address,
        public ?\DateTimeImmutable $deliveryDate,
        public ?\DateTimeImmutable $timeFrom,
        public ?\DateTimeImmutable $timeTo,
        public array $items,
        public ?int $declaredTotalKop,
    ) {
    }
}

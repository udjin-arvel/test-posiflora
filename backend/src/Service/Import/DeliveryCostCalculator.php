<?php

namespace App\Service\Import;

use App\Entity\DeliveryTariff;
use App\Entity\DeliveryType;
use App\Repository\DeliveryTariffRepository;

final class DeliveryCostCalculator
{
    private const URGENCY_FEE_KOP = 20000;
    private const URGENCY_WINDOW_MINUTES = 120;
    private const SHOP_OFFSET = '+03:00';

    public function __construct(
        private readonly DeliveryTariffRepository $tariffs,
    ) {
    }

    public function calculate(
        DeliveryType $deliveryType,
        ?string $district,
        ?string $deliveryDate,
        ?string $timeFrom,
        int $itemsTotalKop,
        ?\DateTimeImmutable $marketplaceCreatedAt,
    ): DeliveryCostResult {
        if ($deliveryType === DeliveryType::Pickup) {
            return new DeliveryCostResult(0, null, false, false, 0, 0);
        }

        $tariff = $this->tariffs->findByCode((string) $district);
        if (!$tariff instanceof DeliveryTariff) {
            throw new \LogicException('Tariff must exist for validated delivery');
        }

        $freeApplied = $tariff->getFreeFromKop() !== null && $itemsTotalKop >= $tariff->getFreeFromKop();
        $baseKop = $freeApplied ? 0 : $tariff->getBaseKop();

        $urgent = false;
        if ($marketplaceCreatedAt !== null && $deliveryDate !== null && $timeFrom !== null) {
            $intervalStart = new \DateTimeImmutable(
                $deliveryDate.'T'.$timeFrom.':00'.self::SHOP_OFFSET,
            );
            $minutes = ($intervalStart->getTimestamp() - $marketplaceCreatedAt->getTimestamp()) / 60;
            $urgent = $minutes < self::URGENCY_WINDOW_MINUTES;
        }
        $urgencyKop = $urgent ? self::URGENCY_FEE_KOP : 0;

        return new DeliveryCostResult(
            $baseKop,
            $tariff->getFreeFromKop(),
            $freeApplied,
            $urgent,
            $urgencyKop,
            $baseKop + $urgencyKop,
        );
    }
}

<?php

namespace App\Service\Import;

use App\Entity\CustomerOrder;
use App\Entity\DeliveryType;
use App\Entity\OrderItem;

final class OrderFactory
{
    public function __construct(
        private readonly DeliveryCostCalculator $deliveryCost,
    ) {
    }

    public function createNew(string $shopId, ParsedOrder $parsed, \DateTimeImmutable $now): CustomerOrder
    {
        $order = new CustomerOrder($shopId, $parsed->externalId);
        $this->fillFromParsed($order, $parsed, $now);

        return $order;
    }

    public function fillFromParsed(CustomerOrder $order, ParsedOrder $parsed, \DateTimeImmutable $now): void
    {
        $itemsTotalKop = 0;
        foreach ($parsed->items as $item) {
            $itemsTotalKop += $item['qty'] * $item['priceKop'];
        }

        $dateStr = $parsed->deliveryDate?->format('Y-m-d');
        $timeFromStr = $parsed->timeFrom?->format('H:i');

        $cost = $this->deliveryCost->calculate(
            $parsed->deliveryType,
            $parsed->district,
            $dateStr,
            $timeFromStr,
            $itemsTotalKop,
            $parsed->marketplaceCreatedAt,
        );

        $order->setStatus($parsed->status);
        $order->setCustomerName($parsed->customerName);
        $order->setCustomerPhone($parsed->customerPhone);
        $order->setDeliveryType($parsed->deliveryType);
        $order->setDistrict($parsed->district);
        $order->setAddress($parsed->address);
        $order->setDeliveryDate($parsed->deliveryDate);
        $order->setTimeFrom($parsed->timeFrom);
        $order->setTimeTo($parsed->timeTo);
        $order->setMarketplaceCreatedAt($parsed->marketplaceCreatedAt);
        $order->setItemsTotalKop($itemsTotalKop);
        $order->setDeclaredTotalKop($parsed->declaredTotalKop);
        $order->setNeedsReview(
            $parsed->declaredTotalKop === null || $parsed->declaredTotalKop !== $itemsTotalKop,
        );
        $order->setDeliveryBaseKop($cost->baseKop);
        $order->setDeliveryFreeFromKop($cost->freeFromKop);
        $order->setDeliveryFreeApplied($cost->freeApplied);
        $order->setDeliveryUrgent($cost->urgent);
        $order->setDeliveryUrgencyKop($cost->urgencyKop);
        $order->setDeliveryTotalKop($cost->totalKop);
        $order->setGrandTotalKop($itemsTotalKop + $cost->totalKop);
        $order->setImportedAt($now);
        $order->setStatusUpdatedAt($now);

        foreach ($parsed->items as $position => $item) {
            $order->addItem(new OrderItem(
                $order,
                $position + 1,
                $item['sku'],
                $item['name'],
                $item['qty'],
                $item['priceKop'],
            ));
        }
    }
}

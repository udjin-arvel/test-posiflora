<?php

namespace App\Service\Order;

use App\Entity\CustomerOrder;
use App\Entity\DeliveryType;
use App\Entity\OrderItem;

final class OrderPresenter
{
    public static function toArray(CustomerOrder $order): array
    {
        $delivery = $order->getDeliveryType() === DeliveryType::Pickup
            ? ['type' => 'pickup']
            : [
                'type' => 'delivery',
                'district' => (string) $order->getDistrict(),
                'address' => (string) $order->getAddress(),
                'date' => $order->getDeliveryDate()?->format('Y-m-d'),
                'timeFrom' => $order->getTimeFrom()?->format('H:i'),
                'timeTo' => $order->getTimeTo()?->format('H:i'),
            ];

        return [
            'shopId' => $order->getShopId(),
            'externalId' => $order->getExternalId(),
            'status' => $order->getStatus()->value,
            'createdAt' => $order->getMarketplaceCreatedAt()?->format(\DateTimeInterface::ATOM),
            'customer' => [
                'name' => $order->getCustomerName(),
                'phone' => $order->getCustomerPhone(),
            ],
            'delivery' => $delivery,
            'items' => array_map(
                static fn (OrderItem $item): array => [
                    'sku' => $item->getSku(),
                    'name' => $item->getName(),
                    'qty' => $item->getQty(),
                    'priceKop' => $item->getPriceKop(),
                ],
                $order->getItems()->toArray(),
            ),
            'itemsTotalKop' => $order->getItemsTotalKop(),
            'declaredTotalKop' => $order->getDeclaredTotalKop(),
            'needsReview' => $order->needsReview(),
            'deliveryCost' => [
                'baseKop' => $order->getDeliveryBaseKop(),
                'freeFromKop' => $order->getDeliveryFreeFromKop(),
                'freeApplied' => $order->isDeliveryFreeApplied(),
                'urgent' => $order->isDeliveryUrgent(),
                'urgencyKop' => $order->getDeliveryUrgencyKop(),
                'totalKop' => $order->getDeliveryTotalKop(),
            ],
            'grandTotalKop' => $order->getGrandTotalKop(),
            'importedAt' => $order->getImportedAt()->format(\DateTimeInterface::ATOM),
            'statusUpdatedAt' => $order->getStatusUpdatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }
}

<?php

namespace App\Service\Import;

use App\Entity\OrderStatus;

final class OrderStatusLabel
{
    public static function label(OrderStatus $status): string
    {
        return match ($status) {
            OrderStatus::New => 'Новый',
            OrderStatus::Accepted => 'Принят',
            OrderStatus::Delivering => 'В доставке',
            OrderStatus::Delivered => 'Доставлен',
            OrderStatus::Cancelled => 'Отменён',
        };
    }
}

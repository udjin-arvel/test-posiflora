<?php

namespace App\Entity;

enum DeliveryType: string
{
    case Pickup = 'pickup';
    case Delivery = 'delivery';
}

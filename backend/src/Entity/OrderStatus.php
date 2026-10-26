<?php

namespace App\Entity;

enum OrderStatus: string
{
    case New = 'new';
    case Accepted = 'accepted';
    case Delivering = 'delivering';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';
}

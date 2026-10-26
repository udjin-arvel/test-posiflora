<?php

namespace App\Service\Import;

final readonly class DeliveryCostResult
{
    public function __construct(
        public int $baseKop,
        public ?int $freeFromKop,
        public bool $freeApplied,
        public bool $urgent,
        public int $urgencyKop,
        public int $totalKop,
    ) {
    }
}

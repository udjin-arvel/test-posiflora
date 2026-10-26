<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'delivery_tariff')]
class DeliveryTariff
{
    #[ORM\Id]
    #[ORM\Column(length: 32)]
    private string $code;

    #[ORM\Column(length: 64)]
    private string $label;

    #[ORM\Column]
    private int $baseKop;

    #[ORM\Column(nullable: true)]
    private ?int $freeFromKop = null;

    public function __construct(string $code, string $label, int $baseKop, ?int $freeFromKop)
    {
        $this->code = $code;
        $this->label = $label;
        $this->baseKop = $baseKop;
        $this->freeFromKop = $freeFromKop;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getBaseKop(): int
    {
        return $this->baseKop;
    }

    public function getFreeFromKop(): ?int
    {
        return $this->freeFromKop;
    }
}

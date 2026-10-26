<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'order_item')]
class OrderItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: CustomerOrder::class, inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private CustomerOrder $order;

    #[ORM\Column]
    private int $position;

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $sku = null;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column]
    private int $qty;

    #[ORM\Column]
    private int $priceKop;

    public function __construct(
        CustomerOrder $order,
        int $position,
        ?string $sku,
        string $name,
        int $qty,
        int $priceKop,
    ) {
        $this->order = $order;
        $this->position = $position;
        $this->sku = $sku;
        $this->name = $name;
        $this->qty = $qty;
        $this->priceKop = $priceKop;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function getSku(): ?string
    {
        return $this->sku;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getQty(): int
    {
        return $this->qty;
    }

    public function getPriceKop(): int
    {
        return $this->priceKop;
    }
}

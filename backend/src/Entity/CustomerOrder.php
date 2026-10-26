<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'customer_order')]
#[ORM\UniqueConstraint(name: 'uniq_shop_external', columns: ['shop_id', 'external_id'])]
#[ORM\Index(name: 'idx_shop_status', columns: ['shop_id', 'status'])]
class CustomerOrder
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 64)]
    private string $shopId;

    #[ORM\Column(length: 64)]
    private string $externalId;

    #[ORM\Column(enumType: OrderStatus::class)]
    private OrderStatus $status;

    #[ORM\Column(length: 255)]
    private string $customerName;

    #[ORM\Column(length: 12)]
    private string $customerPhone;

    #[ORM\Column(enumType: DeliveryType::class)]
    private DeliveryType $deliveryType;

    #[ORM\Column(length: 32, nullable: true)]
    private ?string $district = null;

    #[ORM\Column(length: 512, nullable: true)]
    private ?string $address = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $deliveryDate = null;

    #[ORM\Column(type: Types::TIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $timeFrom = null;

    #[ORM\Column(type: Types::TIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $timeTo = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, precision: 6, nullable: true)]
    private ?\DateTimeImmutable $marketplaceCreatedAt = null;

    #[ORM\Column]
    private int $itemsTotalKop;

    #[ORM\Column(nullable: true)]
    private ?int $declaredTotalKop = null;

    #[ORM\Column]
    private bool $needsReview;

    #[ORM\Column]
    private int $deliveryBaseKop;

    #[ORM\Column(nullable: true)]
    private ?int $deliveryFreeFromKop = null;

    #[ORM\Column]
    private bool $deliveryFreeApplied;

    #[ORM\Column]
    private bool $deliveryUrgent;

    #[ORM\Column]
    private int $deliveryUrgencyKop;

    #[ORM\Column]
    private int $deliveryTotalKop;

    #[ORM\Column]
    private int $grandTotalKop;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, precision: 6)]
    private \DateTimeImmutable $importedAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, precision: 6)]
    private \DateTimeImmutable $statusUpdatedAt;

    /** @var Collection<int, OrderItem> */
    #[ORM\OneToMany(targetEntity: OrderItem::class, mappedBy: 'order', cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private Collection $items;

    public function __construct(string $shopId, string $externalId)
    {
        $this->shopId = $shopId;
        $this->externalId = $externalId;
        $this->items = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getShopId(): string
    {
        return $this->shopId;
    }

    public function getExternalId(): string
    {
        return $this->externalId;
    }

    public function getStatus(): OrderStatus
    {
        return $this->status;
    }

    public function setStatus(OrderStatus $status): void
    {
        $this->status = $status;
    }

    public function getCustomerName(): string
    {
        return $this->customerName;
    }

    public function setCustomerName(string $customerName): void
    {
        $this->customerName = $customerName;
    }

    public function getCustomerPhone(): string
    {
        return $this->customerPhone;
    }

    public function setCustomerPhone(string $customerPhone): void
    {
        $this->customerPhone = $customerPhone;
    }

    public function getDeliveryType(): DeliveryType
    {
        return $this->deliveryType;
    }

    public function setDeliveryType(DeliveryType $deliveryType): void
    {
        $this->deliveryType = $deliveryType;
    }

    public function getDistrict(): ?string
    {
        return $this->district;
    }

    public function setDistrict(?string $district): void
    {
        $this->district = $district;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): void
    {
        $this->address = $address;
    }

    public function getDeliveryDate(): ?\DateTimeImmutable
    {
        return $this->deliveryDate;
    }

    public function setDeliveryDate(?\DateTimeImmutable $deliveryDate): void
    {
        $this->deliveryDate = $deliveryDate;
    }

    public function getTimeFrom(): ?\DateTimeImmutable
    {
        return $this->timeFrom;
    }

    public function setTimeFrom(?\DateTimeImmutable $timeFrom): void
    {
        $this->timeFrom = $timeFrom;
    }

    public function getTimeTo(): ?\DateTimeImmutable
    {
        return $this->timeTo;
    }

    public function setTimeTo(?\DateTimeImmutable $timeTo): void
    {
        $this->timeTo = $timeTo;
    }

    public function getMarketplaceCreatedAt(): ?\DateTimeImmutable
    {
        return $this->marketplaceCreatedAt;
    }

    public function setMarketplaceCreatedAt(?\DateTimeImmutable $marketplaceCreatedAt): void
    {
        $this->marketplaceCreatedAt = $marketplaceCreatedAt;
    }

    public function getItemsTotalKop(): int
    {
        return $this->itemsTotalKop;
    }

    public function setItemsTotalKop(int $itemsTotalKop): void
    {
        $this->itemsTotalKop = $itemsTotalKop;
    }

    public function getDeclaredTotalKop(): ?int
    {
        return $this->declaredTotalKop;
    }

    public function setDeclaredTotalKop(?int $declaredTotalKop): void
    {
        $this->declaredTotalKop = $declaredTotalKop;
    }

    public function needsReview(): bool
    {
        return $this->needsReview;
    }

    public function setNeedsReview(bool $needsReview): void
    {
        $this->needsReview = $needsReview;
    }

    public function getDeliveryBaseKop(): int
    {
        return $this->deliveryBaseKop;
    }

    public function setDeliveryBaseKop(int $deliveryBaseKop): void
    {
        $this->deliveryBaseKop = $deliveryBaseKop;
    }

    public function getDeliveryFreeFromKop(): ?int
    {
        return $this->deliveryFreeFromKop;
    }

    public function setDeliveryFreeFromKop(?int $deliveryFreeFromKop): void
    {
        $this->deliveryFreeFromKop = $deliveryFreeFromKop;
    }

    public function isDeliveryFreeApplied(): bool
    {
        return $this->deliveryFreeApplied;
    }

    public function setDeliveryFreeApplied(bool $deliveryFreeApplied): void
    {
        $this->deliveryFreeApplied = $deliveryFreeApplied;
    }

    public function isDeliveryUrgent(): bool
    {
        return $this->deliveryUrgent;
    }

    public function setDeliveryUrgent(bool $deliveryUrgent): void
    {
        $this->deliveryUrgent = $deliveryUrgent;
    }

    public function getDeliveryUrgencyKop(): int
    {
        return $this->deliveryUrgencyKop;
    }

    public function setDeliveryUrgencyKop(int $deliveryUrgencyKop): void
    {
        $this->deliveryUrgencyKop = $deliveryUrgencyKop;
    }

    public function getDeliveryTotalKop(): int
    {
        return $this->deliveryTotalKop;
    }

    public function setDeliveryTotalKop(int $deliveryTotalKop): void
    {
        $this->deliveryTotalKop = $deliveryTotalKop;
    }

    public function getGrandTotalKop(): int
    {
        return $this->grandTotalKop;
    }

    public function setGrandTotalKop(int $grandTotalKop): void
    {
        $this->grandTotalKop = $grandTotalKop;
    }

    public function getImportedAt(): \DateTimeImmutable
    {
        return $this->importedAt;
    }

    public function setImportedAt(\DateTimeImmutable $importedAt): void
    {
        $this->importedAt = $importedAt;
    }

    public function getStatusUpdatedAt(): \DateTimeImmutable
    {
        return $this->statusUpdatedAt;
    }

    public function setStatusUpdatedAt(\DateTimeImmutable $statusUpdatedAt): void
    {
        $this->statusUpdatedAt = $statusUpdatedAt;
    }

    /** @return Collection<int, OrderItem> */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(OrderItem $item): void
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
        }
    }
}

<?php

namespace App\Tests\Unit;

use App\Entity\DeliveryTariff;
use App\Entity\DeliveryType;
use App\Repository\DeliveryTariffRepository;
use App\Service\Import\DeliveryCostCalculator;
use PHPUnit\Framework\TestCase;

final class DeliveryCostCalculatorTest extends TestCase
{
    private DeliveryCostCalculator $calculator;

    protected function setUp(): void
    {
        $repo = $this->createMock(DeliveryTariffRepository::class);
        $repo->method('findByCode')->willReturnCallback(static function (string $code): ?DeliveryTariff {
            return match ($code) {
                'centre' => new DeliveryTariff('centre', 'Центр', 30000, 500000),
                'north' => new DeliveryTariff('north', 'Север', 40000, 700000),
                'suburb' => new DeliveryTariff('suburb', 'Пригород', 70000, null),
                default => null,
            };
        });
        $this->calculator = new DeliveryCostCalculator($repo);
    }

    public function testPickupIsFree(): void
    {
        $cost = $this->calculator->calculate(DeliveryType::Pickup, null, null, null, 150000, null);
        self::assertSame(0, $cost->totalKop);
    }

    public function testCentreBelowThreshold(): void
    {
        $cost = $this->calculator->calculate(
            DeliveryType::Delivery,
            'centre',
            '2026-10-10',
            '14:00',
            320000,
            new \DateTimeImmutable('2026-10-08T09:00:00+03:00'),
        );
        self::assertSame(30000, $cost->totalKop);
    }

    public function testCentreExactlyAtFreeThreshold(): void
    {
        $cost = $this->calculator->calculate(
            DeliveryType::Delivery,
            'centre',
            '2026-10-10',
            '14:00',
            500000,
            new \DateTimeImmutable('2026-10-08T09:00:00+03:00'),
        );
        self::assertSame(0, $cost->totalKop);
    }

    public function testUrgentAddsTwoHundredRubles(): void
    {
        $cost = $this->calculator->calculate(
            DeliveryType::Delivery,
            'centre',
            '2026-10-10',
            '12:00',
            320000,
            new \DateTimeImmutable('2026-10-10T10:01:00+03:00'),
        );
        self::assertSame(50000, $cost->totalKop);
    }

    public function testExactlyTwoHoursIsNotUrgent(): void
    {
        $cost = $this->calculator->calculate(
            DeliveryType::Delivery,
            'centre',
            '2026-10-10',
            '12:00',
            320000,
            new \DateTimeImmutable('2026-10-10T10:00:00+03:00'),
        );
        self::assertFalse($cost->urgent);
    }

    public function testFreeBaseStillChargesUrgency(): void
    {
        $cost = $this->calculator->calculate(
            DeliveryType::Delivery,
            'centre',
            '2026-10-10',
            '12:00',
            600000,
            new \DateTimeImmutable('2026-10-10T11:00:00+03:00'),
        );
        self::assertSame(20000, $cost->totalKop);
    }

    public function testSuburbNeverFree(): void
    {
        $cost = $this->calculator->calculate(
            DeliveryType::Delivery,
            'suburb',
            '2026-10-10',
            '14:00',
            10000000,
            new \DateTimeImmutable('2026-10-08T09:00:00+03:00'),
        );
        self::assertSame(70000, $cost->totalKop);
    }
}

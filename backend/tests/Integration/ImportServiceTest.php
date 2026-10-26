<?php

namespace App\Tests\Integration;

use App\Entity\OrderStatus;
use App\Repository\CustomerOrderRepository;
use App\Service\Import\ImportService;
use App\Tests\Support\DatabaseTestCase;

final class ImportServiceTest extends DatabaseTestCase
{
    private ImportService $import;

    protected function setUp(): void
    {
        parent::setUp();
        $this->import = static::getContainer()->get(ImportService::class);
    }

    public function testFixtureCreatesSevenOrders(): void
    {
        $report = $this->import->import($this->loadFixture());

        self::assertSame(
            ['created' => 7, 'updated' => 0, 'unchanged' => 0, 'duplicate' => 1, 'rejected' => 3],
            $report['counts'],
        );
        self::assertSame(7, $this->orderCount());
    }

    public function testRejectReasons(): void
    {
        $report = $this->import->import($this->loadFixture());
        $codes = static function (string $id) use ($report): array {
            foreach ($report['records'] as $record) {
                if (($record['externalId'] ?? null) === $id) {
                    return array_column($record['issues'], 'code');
                }
            }

            return [];
        };

        self::assertSame(['unknown_district'], $codes('MP-1006'));
        self::assertSame(['invalid_phone'], $codes('MP-1007'));
        self::assertSame(['unknown_status'], $codes('MP-1008'));
    }

    public function testMp1002DeliverySixHundredRubles(): void
    {
        $this->import->import($this->loadFixture());
        $order = $this->orders()->findOneByShopAndExternalId('demo', 'MP-1002');
        self::assertNotNull($order);
        self::assertSame(60000, $order->getDeliveryTotalKop());
    }

    public function testMp1009NeedsReviewAndTotals(): void
    {
        $this->import->import($this->loadFixture());
        $order = $this->orders()->findOneByShopAndExternalId('demo', 'MP-1009');
        self::assertNotNull($order);
        self::assertTrue($order->needsReview());
        self::assertSame(360000, $order->getItemsTotalKop());
        self::assertSame(430000, $order->getGrandTotalKop());
    }

    public function testStatusMapping(): void
    {
        $this->import->import($this->loadFixture());
        self::assertSame(
            OrderStatus::Delivered,
            $this->orders()->findOneByShopAndExternalId('demo', 'MP-1005')?->getStatus(),
        );
        self::assertSame(
            OrderStatus::Cancelled,
            $this->orders()->findOneByShopAndExternalId('demo', 'MP-1010')?->getStatus(),
        );
    }

    public function testSecondImportIsUnchanged(): void
    {
        $this->import->import($this->loadFixture());
        $report = $this->import->import($this->loadFixture());

        self::assertSame(0, $report['counts']['created']);
        self::assertSame(7, $report['counts']['unchanged']);
        self::assertSame(7, $this->orderCount());
    }

    public function testReimportUpdatesOnlyStatus(): void
    {
        $fixture = $this->loadFixture();
        $this->import->import($fixture);

        $changed = $fixture;
        $changed['orders'][0]['status'] = 'ACCEPTED';
        $changed['orders'][0]['customer'] = ['name' => 'Другое имя', 'phone' => '+79990000000'];

        $report = $this->import->import($changed);
        self::assertSame('updated', $report['records'][0]['outcome']);

        $order = $this->orders()->findOneByShopAndExternalId('demo', 'MP-1001');
        self::assertSame(OrderStatus::Accepted, $order?->getStatus());
        self::assertSame('Анна Лебедева', $order?->getCustomerName());
    }

    public function testFinalStatusDoesNotRollBack(): void
    {
        $fixture = $this->loadFixture();
        $this->import->import($fixture);

        $row = null;
        foreach ($fixture['orders'] as $order) {
            if ($order['id'] === 'MP-1005') {
                $row = $order;
                break;
            }
        }
        self::assertIsArray($row);
        $row['status'] = 'NEW';

        $report = $this->import->import(['orders' => [$row]]);
        self::assertSame('unchanged', $report['records'][0]['outcome']);
        self::assertSame(
            OrderStatus::Delivered,
            $this->orders()->findOneByShopAndExternalId('demo', 'MP-1005')?->getStatus(),
        );
    }

    private function orders(): CustomerOrderRepository
    {
        return static::getContainer()->get(CustomerOrderRepository::class);
    }

    private function orderCount(): int
    {
        $conn = static::getContainer()->get('doctrine')->getConnection();

        return (int) $conn->fetchOne('SELECT COUNT(*) FROM customer_order WHERE shop_id = ?', ['demo']);
    }
}

<?php

namespace App\Tests\Support;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class DatabaseTestCase extends KernelTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        self::bootKernel();
        $this->purgeOrders();
    }

    protected function purgeOrders(): void
    {
        $connection = static::getContainer()->get(EntityManagerInterface::class)->getConnection();
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS=0');
        $connection->executeStatement('DELETE FROM order_item');
        $connection->executeStatement('DELETE FROM customer_order');
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS=1');
    }

    protected function fixturePath(): string
    {
        return dirname(__DIR__).'/fixtures/marketplace-orders.json';
    }

    /** @return array<string, mixed> */
    protected function loadFixture(): array
    {
        $data = json_decode(file_get_contents($this->fixturePath()), true, 512, JSON_THROW_ON_ERROR);

        return is_array($data) ? $data : [];
    }
}

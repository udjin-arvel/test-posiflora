<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ImportControllerTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
        $this->purgeOrders();
    }

    public function testBadPayloadReturns400(): void
    {
        $this->client->request(
            'POST',
            '/api/imports',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: '{"not_orders":[]}',
        );

        self::assertResponseStatusCodeSame(400);
    }

    public function testFixtureReturnsReport(): void
    {
        $payload = file_get_contents(dirname(__DIR__).'/fixtures/marketplace-orders.json');
        self::assertNotFalse($payload);

        $this->client->request(
            'POST',
            '/api/imports',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: $payload,
        );

        self::assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(7, $data['counts']['created']);
        self::assertSame(11, $data['total']);
    }

    private function purgeOrders(): void
    {
        $connection = static::getContainer()->get('doctrine')->getConnection();
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS=0');
        $connection->executeStatement('DELETE FROM order_item');
        $connection->executeStatement('DELETE FROM customer_order');
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS=1');
    }
}

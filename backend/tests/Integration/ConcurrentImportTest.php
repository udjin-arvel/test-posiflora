<?php

namespace App\Tests\Integration;

use App\Tests\Support\DatabaseTestCase;
use Symfony\Component\Process\Process;

final class ConcurrentImportTest extends DatabaseTestCase
{
    public function testTwoParallelImportsCreateSevenOrdersTotal(): void
    {
        $projectDir = static::getContainer()->getParameter('kernel.project_dir');
        $fixture = $this->fixturePath();

        $makeProcess = static fn (): Process => new Process(
            [PHP_BINARY, 'bin/console', 'app:import', $fixture, '--no-ansi'],
            $projectDir,
            ['APP_ENV' => 'test'],
        );

        $first = $makeProcess();
        $second = $makeProcess();
        $first->start();
        $second->start();

        self::assertSame(0, $first->wait());
        self::assertSame(0, $second->wait());

        $reportA = json_decode($first->getOutput(), true, 512, JSON_THROW_ON_ERROR);
        $reportB = json_decode($second->getOutput(), true, 512, JSON_THROW_ON_ERROR);

        $created = $reportA['counts']['created'] + $reportB['counts']['created'];
        self::assertSame(7, $created);

        $conn = static::getContainer()->get('doctrine')->getConnection();
        $count = (int) $conn->fetchOne('SELECT COUNT(*) FROM customer_order WHERE shop_id = ?', ['demo']);
        self::assertSame(7, $count);
    }
}

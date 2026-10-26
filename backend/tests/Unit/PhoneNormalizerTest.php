<?php

namespace App\Tests\Unit;

use App\Service\Import\PhoneNormalizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PhoneNormalizerTest extends TestCase
{
    private PhoneNormalizer $normalizer;

    protected function setUp(): void
    {
        $this->normalizer = new PhoneNormalizer();
    }

    #[DataProvider('validPhones')]
    public function testNormalizes(string $raw, string $expected): void
    {
        self::assertSame($expected, $this->normalizer->normalize($raw));
    }

    /** @return iterable<string, array{string, string}> */
    public static function validPhones(): iterable
    {
        yield '+7 formatted' => ['+7 (900) 123-45-67', '+79001234567'];
        yield '8 prefix compact' => ['89001112233', '+79001112233'];
        yield '8 with spaces' => ['8 900 444 33 22', '+79004443322'];
    }

    #[DataProvider('invalidPhones')]
    public function testRejectsInvalid(string $raw): void
    {
        self::assertNull($this->normalizer->normalize($raw));
    }

    /** @return iterable<string, array{string}> */
    public static function invalidPhones(): iterable
    {
        yield 'too short' => ['123'];
        yield 'foreign country' => ['+1 900 123 45 67'];
        yield 'letters' => ['8900abc4567'];
        yield 'ten digits after +7' => ['+7900123456'];
    }
}

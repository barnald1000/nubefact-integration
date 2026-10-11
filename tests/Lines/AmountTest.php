<?php

declare(strict_types=1);

namespace Savia\NubeFactIntegration\Tests\Lines;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Savia\NubeFactIntegration\Lines\Amount;
use Savia\NubeFactIntegration\Lines\InvalidLine;
use Savia\NubeFactIntegration\Tests\TestCase;

final class AmountTest extends TestCase
{
    public static function validAmounts(): iterable
    {
        yield 'two decimals' => ['534.10'];
        yield 'integer' => ['200'];
        yield 'zero' => ['0.00'];
        yield 'redundant trailing zeros' => ['200.000'];
    }

    public static function invalidAmounts(): iterable
    {
        yield 'negative' => ['-0.01'];
        yield 'three decimals' => ['10.001'];
        yield 'not a number' => ['abc'];
        yield 'empty' => [''];
    }

    #[Test]
    #[DataProvider('validAmounts')]
    public function it_accepts_amounts_with_at_most_two_decimals(string $value): void
    {
        self::assertSame(0, Amount::of($value)->value()->compareTo($value));
    }

    #[Test]
    #[DataProvider('invalidAmounts')]
    public function it_rejects_invalid_amounts(string $value): void
    {
        $this->expectException(InvalidLine::class);
        Amount::of($value);
    }
}

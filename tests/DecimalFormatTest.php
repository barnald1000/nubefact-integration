<?php

declare(strict_types=1);

namespace Savia\NubeFactIntegration\Tests;

use Brick\Math\BigDecimal;
use PHPUnit\Framework\Attributes\Test;
use Savia\NubeFactIntegration\DecimalFormat;

final class DecimalFormatTest extends TestCase
{
    #[Test]
    public function it_writes_a_decimal_as_a_number_rounded_half_up_to_the_scale(): void
    {
        self::assertSame(1.01, DecimalFormat::number(BigDecimal::of('1.005'), 2));
        self::assertSame(1.0, DecimalFormat::number(BigDecimal::of('1.004'), 2));
        self::assertSame(245.0, DecimalFormat::number(BigDecimal::of('245.00'), 2));
    }

    #[Test]
    public function it_keeps_up_to_ten_decimals(): void
    {
        self::assertSame(169.4915254237, DecimalFormat::number(BigDecimal::of('169.49152542372881'), 10));
    }
}

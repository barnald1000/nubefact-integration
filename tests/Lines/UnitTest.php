<?php

declare(strict_types=1);

namespace Savia\NubeFactIntegration\Tests\Lines;

use PHPUnit\Framework\Attributes\Test;
use Savia\NubeFactIntegration\Lines\Unit;
use Savia\NubeFactIntegration\Tests\TestCase;

final class UnitTest extends TestCase
{
    #[Test]
    public function it_names_the_nubefact_product_and_service_units(): void
    {
        self::assertSame('NIU', Unit::product()->code());
        self::assertSame('ZZ', Unit::service()->code());
    }

    #[Test]
    public function it_keeps_a_unit_created_in_the_nubefact_account_as_given(): void
    {
        self::assertSame('KGM', Unit::of('KGM')->code());
        self::assertSame('kgm', Unit::of('kgm')->code());
    }
}

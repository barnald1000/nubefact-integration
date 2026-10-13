<?php

declare(strict_types=1);

namespace Savia\NubeFactIntegration\Tests\Lines;

use PHPUnit\Framework\Attributes\Test;
use Savia\NubeFactIntegration\Lines\Amount;
use Savia\NubeFactIntegration\Lines\IGVTreatment;
use Savia\NubeFactIntegration\Lines\Items;
use Savia\NubeFactIntegration\Lines\Quantity;
use Savia\NubeFactIntegration\Lines\Unit;
use Savia\NubeFactIntegration\Tests\Support\BillableItem;
use Savia\NubeFactIntegration\Tests\TestCase;
use stdClass;
use Vaened\Support\Types\InvalidSafelistItem;

final class ItemsTest extends TestCase
{
    #[Test]
    public function it_holds_lines(): void
    {
        $line = BillableItem::of('MAT-001', 'Matrícula 2026', Unit::service())->sold(
            quantity : Quantity::of(1),
            unitPrice: Amount::of('590.00'),
            tax      : IGVTreatment::taxedIncluded(),
        );

        self::assertSame([$line], (new Items([$line]))->items());
    }

    #[Test]
    public function it_rejects_anything_that_is_not_a_line(): void
    {
        $this->expectException(InvalidSafelistItem::class);

        new Items([new stdClass()]);
    }
}

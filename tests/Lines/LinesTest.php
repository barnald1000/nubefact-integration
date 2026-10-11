<?php

declare(strict_types=1);

namespace Savia\NubeFactIntegration\Tests\Lines;

use PHPUnit\Framework\Attributes\Test;
use Savia\NubeFactIntegration\Lines\Amount;
use Savia\NubeFactIntegration\Lines\IGVTreatment;
use Savia\NubeFactIntegration\Lines\Item;
use Savia\NubeFactIntegration\Lines\Line;
use Savia\NubeFactIntegration\Lines\Lines;
use Savia\NubeFactIntegration\Lines\Quantity;
use Savia\NubeFactIntegration\Lines\Unit;
use Savia\NubeFactIntegration\Tests\TestCase;
use stdClass;
use Vaened\Support\Types\InvalidSafelistItem;

final class LinesTest extends TestCase
{
    #[Test]
    public function it_holds_lines(): void
    {
        $line = Line::onerous(
            item     : Item::of('MAT-001', 'Matrícula 2026', Unit::service()),
            quantity : Quantity::of(1),
            unitPrice: Amount::of('590.00'),
            tax      : IGVTreatment::taxedIncluded(),
        );

        self::assertSame([$line], (new Lines([$line]))->items());
    }

    #[Test]
    public function it_rejects_anything_that_is_not_a_line(): void
    {
        $this->expectException(InvalidSafelistItem::class);

        new Lines([new stdClass()]);
    }
}

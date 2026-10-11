<?php

declare(strict_types=1);

namespace Savia\NubeFactIntegration\Tests\Projection;

use PHPUnit\Framework\Attributes\Test;
use Savia\NubeFactIntegration\Keywords\IgvRate;
use Savia\NubeFactIntegration\Lines\Amount;
use Savia\NubeFactIntegration\Lines\IGVTreatment;
use Savia\NubeFactIntegration\Lines\Item;
use Savia\NubeFactIntegration\Lines\Line;
use Savia\NubeFactIntegration\Lines\Quantity;
use Savia\NubeFactIntegration\Lines\Unit;
use Savia\NubeFactIntegration\Projection\LineProjection;
use Savia\NubeFactIntegration\Projection\LineProjections;
use Savia\NubeFactIntegration\Tests\TestCase;
use Vaened\Support\Types\InvalidSafelistItem;

final class LineProjectionsTest extends TestCase
{
    #[Test]
    public function it_holds_line_projections(): void
    {
        $projection = LineProjection::of(Line::onerous(
            item     : Item::of('MAT-001', 'Matrícula 2026', Unit::service()),
            quantity : Quantity::of(1),
            unitPrice: Amount::of('590.00'),
            tax      : IGVTreatment::taxedIncluded(),
        ), IgvRate::standard());

        self::assertSame([$projection], (new LineProjections([$projection]))->items());
    }

    #[Test]
    public function it_rejects_a_line_that_was_not_calculated(): void
    {
        $this->expectException(InvalidSafelistItem::class);

        new LineProjections([Line::onerous(
            item     : Item::of('MAT-001', 'Matrícula 2026', Unit::service()),
            quantity : Quantity::of(1),
            unitPrice: Amount::of('590.00'),
            tax      : IGVTreatment::taxedIncluded(),
        )]);
    }
}

<?php

declare(strict_types=1);

namespace Savia\NubeFactIntegration\Tests\Projection;

use PHPUnit\Framework\Attributes\Test;
use Savia\NubeFactIntegration\Lines\Amount;
use Savia\NubeFactIntegration\Lines\IGVTreatment;
use Savia\NubeFactIntegration\Lines\Item;
use Savia\NubeFactIntegration\Lines\Line;
use Savia\NubeFactIntegration\Lines\Quantity;
use Savia\NubeFactIntegration\Lines\Unit;
use Savia\NubeFactIntegration\Projection\DocumentProjection;
use Savia\NubeFactIntegration\Tests\Support\LinesTransaction;
use Savia\NubeFactIntegration\Tests\TestCase;

use function intdiv;
use function sprintf;

final class DocumentProjectionTest extends TestCase
{
    #[Test]
    public function it_adds_the_rounded_lines_instead_of_rounding_the_document(): void
    {
        $document = self::project(
            self::line('10.00', IGVTreatment::taxedIncluded()),
            self::line('10.00', IGVTreatment::taxedIncluded()),
            self::line('10.00', IGVTreatment::taxedIncluded()),
        );

        self::assertCount(3, $document->lines()->items());
        self::assertSame('25.41', (string) $document->totalTaxed());
        self::assertSame('4.59', (string) $document->totalTaxes());
        self::assertSame('30.00', (string) $document->total());
    }

    #[Test]
    public function it_puts_each_line_in_its_own_header_total(): void
    {
        $document = self::project(
            self::line('590.00', IGVTreatment::taxedIncluded()),
            self::line('100.00', IGVTreatment::exonerated(), '20.00'),
            self::line('50.00', IGVTreatment::unaffected()),
        );

        self::assertSame('500.00', (string) $document->totalTaxed());
        self::assertSame('80.00', (string) $document->totalExonerated());
        self::assertSame('50.00', (string) $document->totalUnaffected());
        self::assertSame('20.00', (string) $document->totalDiscounts());
        self::assertSame('90.00', (string) $document->totalTaxes());
        self::assertSame('720.00', (string) $document->total());
    }

    #[Test]
    public function it_adds_the_discount_before_igv_of_each_line(): void
    {
        $document = self::project(
            self::line('590.00', IGVTreatment::taxedIncluded(), '590.00'),
            self::line('118.00', IGVTreatment::taxedIncluded()),
        );

        self::assertSame('500.00', (string) $document->totalDiscounts());
        self::assertSame('100.00', (string) $document->totalTaxed());
        self::assertSame('18.00', (string) $document->totalTaxes());
        self::assertSame('118.00', (string) $document->total());
    }

    #[Test]
    public function its_total_is_always_the_sum_of_its_parts(): void
    {
        for ($cents = 1; $cents <= 500; ++$cents) {
            $price = sprintf('%d.%02d', intdiv($cents, 100), $cents % 100);

            foreach ([IGVTreatment::taxedIncluded(), IGVTreatment::taxedExcluded()] as $treatment) {
                $document = self::project(self::line($price, $treatment));

                self::assertSame(
                    (string) $document->total(),
                    (string) $document->totalTaxed()->plus($document->totalExonerated())->plus($document->totalUnaffected())->plus($document->totalTaxes()),
                    "A single line of $price must add up.",
                );
            }
        }
    }

    private static function project(Line ...$lines): DocumentProjection
    {
        return DocumentProjection::of(new LinesTransaction(...$lines));
    }

    private static function line(string $price, IGVTreatment $treatment, ?string $discount = null): Line
    {
        return Line::onerous(
            item     : Item::of('MAT-001', 'Matrícula 2026', Unit::service()),
            quantity : Quantity::of(1),
            unitPrice: Amount::of($price),
            tax      : $treatment,
            discount : $discount === null ? null : Amount::of($discount),
        );
    }
}

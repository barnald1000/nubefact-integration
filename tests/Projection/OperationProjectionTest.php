<?php

declare(strict_types=1);

namespace Savia\NubeFactIntegration\Tests\Projection;

use PHPUnit\Framework\Attributes\Test;
use Savia\NubeFactIntegration\Contracts\Item;
use Savia\NubeFactIntegration\Keywords\Currency;
use Savia\NubeFactIntegration\Keywords\IgvRate;
use Savia\NubeFactIntegration\Lines\Amount;
use Savia\NubeFactIntegration\Lines\IGVTreatment;
use Savia\NubeFactIntegration\Lines\Items;
use Savia\NubeFactIntegration\Lines\Quantity;
use Savia\NubeFactIntegration\Lines\Unit;
use Savia\NubeFactIntegration\Operation;
use Savia\NubeFactIntegration\Projection\OperationProjection;
use Savia\NubeFactIntegration\Tests\Support\BillableItem;
use Savia\NubeFactIntegration\Tests\Support\CountingItem;
use Savia\NubeFactIntegration\Tests\TestCase;

use function intdiv;
use function sprintf;

final class OperationProjectionTest extends TestCase
{
    #[Test]
    public function each_result_keeps_the_item_it_was_calculated_from(): void
    {
        $fee  = self::line('500.00', IGVTreatment::taxedIncluded(), '250.00');
        $book = self::line('80.00', IGVTreatment::exonerated());

        [$feeResult, $bookResult] = self::project($fee, $book)->lines()->items();

        self::assertSame($fee, $feeResult->item());
        self::assertSame('250.00', (string) $feeResult->total());
        self::assertSame($book, $bookResult->item());
        self::assertSame('80.00', (string) $bookResult->total());
    }

    #[Test]
    public function it_reads_the_line_of_each_item_once(): void
    {
        $item = new CountingItem(self::line('118.00', IGVTreatment::taxedIncluded()));

        self::project($item);

        self::assertSame(1, $item->lineCalls);
    }

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

        self::assertSame('630.00', (string) $document->subtotal());
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

    #[Test]
    public function it_keeps_the_lines_in_the_order_they_were_given(): void
    {
        $document = self::project(
            self::line('118.00', IGVTreatment::taxedIncluded()),
            self::line('50.00', IGVTreatment::exonerated()),
            self::line('20.00', IGVTreatment::unaffected()),
        );

        $subtotals = array_map(static fn($line): string => (string) $line->subtotal(), $document->lines()->items());

        self::assertSame(['100.00', '50.00', '20.00'], $subtotals);
    }

    #[Test]
    public function every_line_is_calculated_with_the_rate_of_its_operation(): void
    {
        $operation = Operation::of(Currency::PEN, IgvRate::of(10), new Items([self::line('110.00', IGVTreatment::taxedIncluded())]));
        $document  = $operation->project();

        self::assertSame('100.00', (string) $document->lines()->items()[0]->subtotal());
        self::assertSame('10.00', (string) $document->totalTaxes());
        self::assertSame('110.00', (string) $document->total());
    }

    #[Test]
    public function a_document_without_lines_has_zero_totals(): void
    {
        $document = self::project();

        self::assertSame([], $document->lines()->items());
        self::assertSame('0.00', (string) $document->total());
        self::assertSame('0.00', (string) $document->totalTaxes());
        self::assertSame('0.00', (string) $document->totalTaxed());
    }

    private static function project(Item ...$items): OperationProjection
    {
        return Operation::of(Currency::PEN, IgvRate::standard(), new Items($items))->project();
    }

    private static function line(string $price, IGVTreatment $treatment, ?string $discount = null): BillableItem
    {
        return BillableItem::of('MAT-001', 'Matrícula 2026', Unit::service())->sold(
            quantity : Quantity::of(1),
            unitPrice: Amount::of($price),
            tax      : $treatment,
            discount : $discount === null ? null : Amount::of($discount),
        );
    }
}

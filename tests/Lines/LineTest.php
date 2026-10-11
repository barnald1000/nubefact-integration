<?php

declare(strict_types=1);

namespace Savia\NubeFactIntegration\Tests\Lines;

use Brick\Math\BigDecimal;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Savia\NubeFactIntegration\Keywords\IgvRate;
use Savia\NubeFactIntegration\Keywords\IGVType;
use Savia\NubeFactIntegration\Lines\Amount;
use Savia\NubeFactIntegration\Lines\IGVTreatment;
use Savia\NubeFactIntegration\Lines\InvalidLine;
use Savia\NubeFactIntegration\Lines\Item;
use Savia\NubeFactIntegration\Lines\Line;
use Savia\NubeFactIntegration\Lines\Quantity;
use Savia\NubeFactIntegration\Lines\Unit;
use Savia\NubeFactIntegration\Projection\LineProjection;
use Savia\NubeFactIntegration\Tests\TestCase;

final class LineTest extends TestCase
{
    public static function nonTaxedTreatments(): iterable
    {
        yield 'exonerated' => [IGVTreatment::exonerated(), IGVType::ExoneratedOnerous];
        yield 'unaffected' => [IGVTreatment::unaffected(), IGVType::ExemptOnerous];
    }

    public static function nonTaxedTreatmentValues(): iterable
    {
        yield 'exonerated' => [IGVTreatment::exonerated()];
        yield 'unaffected' => [IGVTreatment::unaffected()];
    }

    public static function taxedExcludedLines(): iterable
    {
        yield 'discount on one unit' => [1, '500.00', '100.00', ['500', '590', '100'], ['400.00', '72.00', '472.00']];
        yield 'several units' => [5, '20.00', '10.00', ['20', '23.6', '10'], ['90.00', '16.20', '106.20']];
    }

    #[Test]
    public function it_projects_a_taxed_line_without_a_discount(): void
    {
        $projection = $this->project(Line::onerous(
            item     : self::item(),
            quantity : Quantity::of(1),
            unitPrice: Amount::of('590.00'),
            tax      : IGVTreatment::taxedIncluded(),
        ));

        self::assertSame('0.00', (string) $projection->totalDiscounts());
        self::assertSame('500.00', (string) $projection->subtotal());
        self::assertSame('90.00', (string) $projection->totalTaxes());
        self::assertSame('590.00', (string) $projection->total());
    }

    #[Test]
    public function it_projects_a_taxed_line_from_original_commercial_facts(): void
    {
        $projection = $this->project(Line::onerous(
            item     : self::item(),
            quantity : Quantity::of(1),
            unitPrice: Amount::of('534.10'),
            tax      : IGVTreatment::taxedIncluded(),
            discount : Amount::of('289.10'),
        ));

        self::assertAmount('452.6271186441', $projection->unitValue());
        self::assertAmount('534.10', $projection->unitPrice());
        self::assertSame(IGVType::GravadoOnerous, $projection->igvType());
        self::assertSame('207.63', (string) $projection->subtotal());
        self::assertSame('37.37', (string) $projection->totalTaxes());
        self::assertSame('245.00', (string) $projection->total());
        self::assertSame('245.00', (string) $projection->totalDiscounts());
    }

    #[Test]
    public function it_accepts_a_full_discount_on_a_taxed_line(): void
    {
        $projection = $this->project(Line::onerous(
            item     : self::item(),
            quantity : Quantity::of(1),
            unitPrice: Amount::of('590.00'),
            discount : Amount::of('590.00'),
            tax      : IGVTreatment::taxedIncluded(),
        ));

        self::assertAmount('500', $projection->unitValue());
        self::assertSame('500.00', (string) $projection->totalDiscounts());
        self::assertSame('0.00', (string) $projection->subtotal());
        self::assertSame('0.00', (string) $projection->totalTaxes());
        self::assertSame('0.00', (string) $projection->total());
    }

    #[Test]
    #[DataProvider('nonTaxedTreatments')]
    public function it_projects_non_taxed_onerous_lines(IGVTreatment $treatment, IGVType $type): void
    {
        $projection = $this->project(Line::onerous(
            item     : self::item(),
            quantity : Quantity::of(2),
            unitPrice: Amount::of('50.00'),
            discount : Amount::of('5.00'),
            tax      : $treatment,
        ));

        self::assertSame($type, $projection->igvType());
        self::assertAmount('50', $projection->unitValue());
        self::assertAmount('50', $projection->unitPrice());
        self::assertSame('5.00', (string) $projection->totalDiscounts());
        self::assertSame('95.00', (string) $projection->subtotal());
        self::assertSame('0.00', (string) $projection->totalTaxes());
        self::assertSame('95.00', (string) $projection->total());
    }

    #[Test]
    #[DataProvider('nonTaxedTreatmentValues')]
    public function it_accepts_a_full_discount_on_non_taxed_onerous_lines(IGVTreatment $treatment): void
    {
        $projection = $this->project(Line::onerous(
            item     : self::item(),
            quantity : Quantity::of(1),
            unitPrice: Amount::of('100.00'),
            discount : Amount::of('100.00'),
            tax      : $treatment,
        ));

        self::assertSame('0.00', (string) $projection->subtotal());
        self::assertSame('0.00', (string) $projection->totalTaxes());
        self::assertSame('0.00', (string) $projection->total());
    }

    #[Test]
    #[DataProvider('taxedExcludedLines')]
    public function it_adds_igv_on_top_of_a_taxed_line_with_amounts_before_igv(
        int $quantity,
        string $unitPrice,
        string $discount,
        array $calculatedUnitAmounts,
        array $results,
    ): void {
        $projection = $this->project(Line::onerous(
            item     : self::item(),
            quantity : Quantity::of($quantity),
            unitPrice: Amount::of($unitPrice),
            tax      : IGVTreatment::taxedExcluded(),
            discount : Amount::of($discount),
        ));

        [$unitValue, $unitPriceWithIgv, $discountBeforeIgv] = $calculatedUnitAmounts;
        [$subtotal, $tax, $total]                           = $results;

        self::assertSame(IGVType::GravadoOnerous, $projection->igvType());
        self::assertAmount($unitValue, $projection->unitValue());
        self::assertAmount($unitPriceWithIgv, $projection->unitPrice());
        self::assertAmount($discountBeforeIgv, $projection->totalDiscounts());
        self::assertSame($subtotal, (string) $projection->subtotal());
        self::assertSame($tax, (string) $projection->totalTaxes());
        self::assertSame($total, (string) $projection->total());
    }

    #[Test]
    public function it_accepts_a_full_discount_on_a_taxed_line_with_amounts_before_igv(): void
    {
        $projection = $this->project(Line::onerous(
            item     : self::item(),
            quantity : Quantity::of(1),
            unitPrice: Amount::of('100.00'),
            tax      : IGVTreatment::taxedExcluded(),
            discount : Amount::of('100.00'),
        ));

        self::assertSame('0.00', (string) $projection->subtotal());
        self::assertSame('0.00', (string) $projection->totalTaxes());
        self::assertSame('0.00', (string) $projection->total());
    }

    #[Test]
    public function it_rejects_a_non_positive_quantity(): void
    {
        $this->expectException(InvalidLine::class);
        Quantity::of(0);
    }

    #[Test]
    public function it_rejects_a_negative_quantity(): void
    {
        $this->expectException(InvalidLine::class);
        Quantity::of(-1);
    }

    #[Test]
    public function it_rejects_a_zero_unit_price(): void
    {
        $this->expectException(InvalidLine::class);

        Line::onerous(
            item     : self::item(),
            quantity : Quantity::of(1),
            unitPrice: Amount::of('0.00'),
            tax      : IGVTreatment::taxedIncluded(),
        );
    }

    #[Test]
    public function it_rejects_a_discount_larger_than_the_original_line_amount(): void
    {
        $this->expectException(InvalidLine::class);

        Line::onerous(
            item     : self::item(),
            quantity : Quantity::of(1),
            unitPrice: Amount::of('10.00'),
            discount : Amount::of('10.01'),
            tax      : IGVTreatment::taxedIncluded(),
        );
    }

    #[Test]
    public function it_compares_the_discount_with_the_whole_line_amount_not_with_one_unit(): void
    {
        $line = Line::onerous(
            item     : self::item(),
            quantity : Quantity::of(3),
            unitPrice: Amount::of('10.00'),
            tax      : IGVTreatment::taxedIncluded(),
            discount : Amount::of('30.00'),
        );

        self::assertSame('30.00', (string) $line->discount()?->value());

        $this->expectException(InvalidLine::class);

        Line::onerous(
            item     : self::item(),
            quantity : Quantity::of(3),
            unitPrice: Amount::of('10.00'),
            tax      : IGVTreatment::taxedIncluded(),
            discount : Amount::of('30.01'),
        );
    }

    #[Test]
    public function it_exposes_the_facts_it_was_built_from(): void
    {
        $item     = self::item();
        $quantity = Quantity::of(2);
        $price    = Amount::of('50.00');
        $tax      = IGVTreatment::exonerated();
        $line     = Line::onerous($item, $quantity, $price, $tax);

        self::assertSame($item, $line->item());
        self::assertSame($quantity, $line->quantity());
        self::assertSame($price, $line->unitPrice());
        self::assertSame($tax, $line->tax());
        self::assertNull($line->discount());
    }

    #[Test]
    public function it_uses_the_standard_document_igv_rate(): void
    {
        self::assertSame('18.00', (string) IgvRate::standard()->percentage());
    }

    private function project(Line $line): LineProjection
    {
        return LineProjection::of($line, IgvRate::standard());
    }

    private static function assertAmount(string $expected, ?BigDecimal $actual): void
    {
        self::assertNotNull($actual);
        self::assertTrue($actual->isEqualTo($expected), "Expected $expected, got $actual.");
    }

    private static function item(): Item
    {
        return Item::of('MAT-001', 'Matrícula 2026', Unit::service());
    }
}

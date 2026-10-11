<?php

declare(strict_types=1);

namespace Savia\NubeFactIntegration\Tests\Lines;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Savia\NubeFactIntegration\Keywords\IgvRate;
use Savia\NubeFactIntegration\Lines\Amount;
use Savia\NubeFactIntegration\Lines\IGVTreatment;
use Savia\NubeFactIntegration\Lines\InvalidLine;
use Savia\NubeFactIntegration\Lines\Item;
use Savia\NubeFactIntegration\Lines\Line;
use Savia\NubeFactIntegration\Lines\Projection\LineProjection;
use Savia\NubeFactIntegration\Lines\Quantity;
use Savia\NubeFactIntegration\Lines\Unit;
use Savia\NubeFactIntegration\Tests\TestCase;

final class LineTest extends TestCase
{
    public static function nonTaxedTreatments(): iterable
    {
        yield 'exonerated' => [IGVTreatment::exonerated(), 8];
        yield 'unaffected' => [IGVTreatment::unaffected(), 9];
    }

    public static function nonTaxedTreatmentValues(): iterable
    {
        yield 'exonerated' => [IGVTreatment::exonerated()];
        yield 'unaffected' => [IGVTreatment::unaffected()];
    }

    #[Test]
    public function it_projects_a_taxed_line_without_a_discount(): void
    {
        $projection = $this->project(Line::onerous(
            item     : self::item(),
            quantity : Quantity::of('1'),
            unitPrice: Amount::of('590.00'),
            tax      : IGVTreatment::taxedIncluded(),
        ));

        self::assertArrayNotHasKey('descuento', $projection->toNubefact());
        self::assertSame('500', $projection->toNubefact()['subtotal']);
        self::assertSame('90', $projection->toNubefact()['igv']);
        self::assertSame('590', $projection->toNubefact()['total']);
    }

    #[Test]
    public function it_projects_a_taxed_line_from_original_commercial_facts(): void
    {
        $projection = $this->project(Line::onerous(
            item     : self::item(),
            quantity : Quantity::of('1'),
            unitPrice: Amount::of('534.10'),
            tax      : IGVTreatment::taxedIncluded(),
            discount : Amount::of('289.10'),
        ));

        self::assertSame([
            'unidad_de_medida' => 'ZZ',
            'descripcion'      => 'Matrícula 2026',
            'cantidad'         => '1',
            'valor_unitario'   => '452.6271186441',
            'precio_unitario'  => '534.1',
            'subtotal'         => '207.63',
            'tipo_de_igv'      => 1,
            'igv'              => '37.37',
            'total'            => '245',
            'codigo'           => 'MAT-001',
            'descuento'        => '245',
        ], $projection->toNubefact());

        self::assertSame('207.63', (string) $projection->subtotal());
        self::assertSame('37.37', (string) $projection->tax());
        self::assertSame('245.00', (string) $projection->total());
        self::assertSame('245.00', (string) $projection->discount());
    }

    #[Test]
    public function it_accepts_a_full_discount_on_a_taxed_line(): void
    {
        $projection = $this->project(Line::onerous(
            item     : self::item(),
            quantity : Quantity::of('1'),
            unitPrice: Amount::of('590.00'),
            discount : Amount::of('590.00'),
            tax      : IGVTreatment::taxedIncluded(),
        ));

        self::assertSame('500', $projection->toNubefact()['valor_unitario']);
        self::assertSame('500', $projection->toNubefact()['descuento']);
        self::assertSame('0', $projection->toNubefact()['subtotal']);
        self::assertSame('0', $projection->toNubefact()['igv']);
        self::assertSame('0', $projection->toNubefact()['total']);
    }

    #[Test]
    #[DataProvider('nonTaxedTreatments')]
    public function it_projects_non_taxed_onerous_lines(IGVTreatment $treatment, int $nubefactType): void
    {
        $projection = $this->project(Line::onerous(
            item     : self::item(),
            quantity : Quantity::of('2'),
            unitPrice: Amount::of('50.00'),
            discount : Amount::of('5.00'),
            tax      : $treatment,
        ));

        self::assertSame($nubefactType, $projection->toNubefact()['tipo_de_igv']);
        self::assertSame('50', $projection->toNubefact()['valor_unitario']);
        self::assertSame('50', $projection->toNubefact()['precio_unitario']);
        self::assertSame('5', $projection->toNubefact()['descuento']);
        self::assertSame('95', $projection->toNubefact()['subtotal']);
        self::assertSame('0', $projection->toNubefact()['igv']);
        self::assertSame('95', $projection->toNubefact()['total']);
        self::assertSame('95.00', (string) $projection->subtotal());
        self::assertSame('0.00', (string) $projection->tax());
    }

    #[Test]
    #[DataProvider('nonTaxedTreatmentValues')]
    public function it_accepts_a_full_discount_on_non_taxed_onerous_lines(IGVTreatment $treatment): void
    {
        $projection = $this->project(Line::onerous(
            item     : self::item(),
            quantity : Quantity::of('1'),
            unitPrice: Amount::of('100.00'),
            discount : Amount::of('100.00'),
            tax      : $treatment,
        ));

        self::assertSame('0', $projection->toNubefact()['subtotal']);
        self::assertSame('0', $projection->toNubefact()['igv']);
        self::assertSame('0', $projection->toNubefact()['total']);
    }

    public static function taxedExcludedLines(): iterable
    {
        yield 'discount on one unit' => ['1', '500.00', '100.00', ['500', '590', '100'], ['400.00', '72.00', '472.00']];
        yield 'several units' => ['5', '20.00', '10.00', ['20', '23.6', '10'], ['90.00', '16.20', '106.20']];
    }

    #[Test]
    #[DataProvider('taxedExcludedLines')]
    public function it_adds_igv_on_top_of_a_taxed_line_with_amounts_before_igv(
        string $quantity,
        string $unitPrice,
        string $discount,
        array $nubefactAmounts,
        array $results,
    ): void {
        $projection = $this->project(Line::onerous(
            item     : self::item(),
            quantity : Quantity::of($quantity),
            unitPrice: Amount::of($unitPrice),
            tax      : IGVTreatment::taxedExcluded(),
            discount : Amount::of($discount),
        ));

        [$unitValue, $unitPriceWithIgv, $discountBeforeIgv] = $nubefactAmounts;
        [$subtotal, $tax, $total]                           = $results;

        self::assertSame(1, $projection->toNubefact()['tipo_de_igv']);
        self::assertSame($unitValue, $projection->toNubefact()['valor_unitario']);
        self::assertSame($unitPriceWithIgv, $projection->toNubefact()['precio_unitario']);
        self::assertSame($discountBeforeIgv, $projection->toNubefact()['descuento']);
        self::assertSame($subtotal, (string) $projection->subtotal());
        self::assertSame($tax, (string) $projection->tax());
        self::assertSame($total, (string) $projection->total());
    }

    #[Test]
    public function it_accepts_a_full_discount_on_a_taxed_line_with_amounts_before_igv(): void
    {
        $projection = $this->project(Line::onerous(
            item     : self::item(),
            quantity : Quantity::of('1'),
            unitPrice: Amount::of('100.00'),
            tax      : IGVTreatment::taxedExcluded(),
            discount : Amount::of('100.00'),
        ));

        self::assertSame('0.00', (string) $projection->subtotal());
        self::assertSame('0.00', (string) $projection->tax());
        self::assertSame('0.00', (string) $projection->total());
    }

    #[Test]
    public function it_rejects_a_non_positive_quantity(): void
    {
        $this->expectException(InvalidLine::class);
        Quantity::of('0');
    }

    #[Test]
    public function it_rejects_a_zero_unit_price(): void
    {
        $this->expectException(InvalidLine::class);

        Line::onerous(
            item     : self::item(),
            quantity : Quantity::of('1'),
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
            quantity : Quantity::of('1'),
            unitPrice: Amount::of('10.00'),
            discount : Amount::of('10.01'),
            tax      : IGVTreatment::taxedIncluded(),
        );
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

    private static function item(): Item
    {
        return Item::of('MAT-001', 'Matrícula 2026', Unit::service());
    }
}

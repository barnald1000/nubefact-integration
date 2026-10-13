<?php

declare(strict_types=1);

namespace Savia\NubeFactIntegration\Tests\Projection;

use PHPUnit\Framework\Attributes\Test;
use Savia\NubeFactIntegration\Actions\Payments\HeaderSchema;
use Savia\NubeFactIntegration\Actions\Payments\LineSchema;
use Savia\NubeFactIntegration\Lines\Amount;
use Savia\NubeFactIntegration\Lines\IGVTreatment;
use Savia\NubeFactIntegration\Lines\Quantity;
use Savia\NubeFactIntegration\Lines\Unit;
use Savia\NubeFactIntegration\Projection\DocumentProjection;
use Savia\NubeFactIntegration\Projection\LineProjection;
use Savia\NubeFactIntegration\Tests\Support\BillableItem;
use Savia\NubeFactIntegration\Tests\Support\ItemsTransaction;
use Savia\NubeFactIntegration\Tests\TestCase;

use function array_intersect_key;
use function array_map;

/**
 * Nubefact spreadsheet examples
 *
 * Reproduces, field by field, the documents of Nubefact's "Manual Cálculo JSON TXT" spreadsheet. The spreadsheet gives
 * the unit value before IGV as input, so its taxed lines are built with IGV excluded. Its examples have nothing to
 * round, so the library must produce exactly the same figures.
 */
final class NubefactSpreadsheetExamplesTest extends TestCase
{
    private const MONEY_FIELDS = [
        'total_descuento' => 0,
        'total_gravada'   => 0,
        'total_exonerada' => 0,
        'total_inafecta'  => 0,
        'total_igv'       => 0,
        'total'           => 0,
    ];

    private const ITEM_FIELDS = [
        'cantidad'        => 0,
        'valor_unitario'  => 0,
        'precio_unitario' => 0,
        'descuento'       => 0,
        'subtotal'        => 0,
        'tipo_de_igv'     => 0,
        'igv'             => 0,
        'total'           => 0,
    ];

    #[Test]
    public function the_gravada_sheet(): void
    {
        [$header, $items] = self::send(
            self::line(1, '500.00', IGVTreatment::taxedExcluded()),
            self::line(5, '20.00', IGVTreatment::taxedExcluded()),
        );

        self::assertSame([
            'total_descuento' => 0.0,
            'total_gravada'   => 600.0,
            'total_exonerada' => 0.0,
            'total_inafecta'  => 0.0,
            'total_igv'       => 108.0,
            'total'           => 708.0,
        ], $header);

        self::assertSame([
            ['cantidad' => 1, 'valor_unitario' => 500.0, 'precio_unitario' => 590.0, 'descuento' => 0.0, 'subtotal' => 500.0, 'tipo_de_igv' => 1, 'igv' => 90.0, 'total' => 590.0],
            ['cantidad' => 5, 'valor_unitario' => 20.0, 'precio_unitario' => 23.6, 'descuento' => 0.0, 'subtotal' => 100.0, 'tipo_de_igv' => 1, 'igv' => 18.0, 'total' => 118.0],
        ], $items);
    }

    #[Test]
    public function the_dscto_item_sheet(): void
    {
        [$header, $items] = self::send(
            self::line(1, '500.00', IGVTreatment::taxedExcluded(), '200.00'),
            self::line(5, '20.00', IGVTreatment::unaffected(), '10.00'),
        );

        self::assertSame([
            'total_descuento' => 210.0,
            'total_gravada'   => 300.0,
            'total_exonerada' => 0.0,
            'total_inafecta'  => 90.0,
            'total_igv'       => 54.0,
            'total'           => 444.0,
        ], $header);

        self::assertSame([
            ['cantidad' => 1, 'valor_unitario' => 500.0, 'precio_unitario' => 590.0, 'descuento' => 200.0, 'subtotal' => 300.0, 'tipo_de_igv' => 1, 'igv' => 54.0, 'total' => 354.0],
            ['cantidad' => 5, 'valor_unitario' => 20.0, 'precio_unitario' => 20.0, 'descuento' => 10.0, 'subtotal' => 90.0, 'tipo_de_igv' => 9, 'igv' => 0.0, 'total' => 90.0],
        ], $items);
    }

    #[Test]
    public function the_ft_exonerada_sheet(): void
    {
        [$header, $items] = self::send(
            self::line(1, '500.00', IGVTreatment::exonerated()),
            self::line(5, '20.00', IGVTreatment::exonerated()),
        );

        self::assertSame([
            'total_descuento' => 0.0,
            'total_gravada'   => 0.0,
            'total_exonerada' => 600.0,
            'total_inafecta'  => 0.0,
            'total_igv'       => 0.0,
            'total'           => 600.0,
        ], $header);

        self::assertSame([
            ['cantidad' => 1, 'valor_unitario' => 500.0, 'precio_unitario' => 500.0, 'descuento' => 0.0, 'subtotal' => 500.0, 'tipo_de_igv' => 8, 'igv' => 0.0, 'total' => 500.0],
            ['cantidad' => 5, 'valor_unitario' => 20.0, 'precio_unitario' => 20.0, 'descuento' => 0.0, 'subtotal' => 100.0, 'tipo_de_igv' => 8, 'igv' => 0.0, 'total' => 100.0],
        ], $items);
    }

    #[Test]
    public function a_taxed_line_given_with_igv_included_matches_the_spreadsheet_too(): void
    {
        [, $items] = self::send(self::line(1, '590.00', IGVTreatment::taxedIncluded()));

        self::assertSame(
            ['cantidad' => 1, 'valor_unitario' => 500.0, 'precio_unitario' => 590.0, 'descuento' => 0.0, 'subtotal' => 500.0, 'tipo_de_igv' => 1, 'igv' => 90.0, 'total' => 590.0],
            $items[0],
        );
    }

    /**
     * @return array{array<string, float>, list<array<string, int|float>>}
     */
    private static function send(BillableItem ...$lines): array
    {
        $transaction = new ItemsTransaction(...$lines);
        $document    = $transaction->operation()->project();
        $header      = array_intersect_key((new HeaderSchema(DocumentProjection::of($transaction)))->value(), self::MONEY_FIELDS);
        $items       = array_map(
            static fn(LineProjection $line): array => array_intersect_key((new LineSchema($line))->value(), self::ITEM_FIELDS),
            $document->lines()->items(),
        );

        return [self::ordered($header, self::MONEY_FIELDS), array_map(static fn(array $item): array => self::ordered($item, self::ITEM_FIELDS), $items)];
    }

    private static function ordered(array $values, array $order): array
    {
        return array_map(static fn(string $key): int|float => $values[$key], array_combine(array_keys($order), array_keys($order)));
    }

    private static function line(int $quantity, string $unitPrice, IGVTreatment $treatment, ?string $discount = null): BillableItem
    {
        return BillableItem::of('P001', 'DETALLE', Unit::product())->sold(
            quantity : Quantity::of($quantity),
            unitPrice: Amount::of($unitPrice),
            tax      : $treatment,
            discount : $discount === null ? null : Amount::of($discount),
        );
    }
}

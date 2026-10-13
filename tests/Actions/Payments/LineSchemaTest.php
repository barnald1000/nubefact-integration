<?php

declare(strict_types=1);

namespace Savia\NubeFactIntegration\Tests\Actions\Payments;

use PHPUnit\Framework\Attributes\Test;
use Savia\NubeFactIntegration\Actions\Payments\LineSchema;
use Savia\NubeFactIntegration\Contracts\Item;
use Savia\NubeFactIntegration\Keywords\IgvRate;
use Savia\NubeFactIntegration\Lines\Amount;
use Savia\NubeFactIntegration\Lines\IGVTreatment;
use Savia\NubeFactIntegration\Lines\Quantity;
use Savia\NubeFactIntegration\Lines\Unit;
use Savia\NubeFactIntegration\Projection\LineProjection;
use Savia\NubeFactIntegration\Tests\Support\BillableItem;
use Savia\NubeFactIntegration\Tests\TestCase;

final class LineSchemaTest extends TestCase
{
    #[Test]
    public function it_translates_a_calculated_line_into_a_nubefact_item(): void
    {
        $schema = self::schema(BillableItem::of('MAT-001', 'Matrícula 2026', Unit::service())->sold(
            quantity : Quantity::of(1),
            unitPrice: Amount::of('534.10'),
            tax      : IGVTreatment::taxedIncluded(),
            discount : Amount::of('289.10'),
        ));

        self::assertSame([
            'unidad_de_medida' => 'ZZ',
            'codigo'           => 'MAT-001',
            'descripcion'      => 'Matrícula 2026',
            'cantidad'         => 1,
            'valor_unitario'   => 452.6271186441,
            'precio_unitario'  => 534.1,
            'descuento'        => 245.0,
            'subtotal'         => 207.63,
            'tipo_de_igv'      => 1,
            'igv'              => 37.37,
            'total'            => 245.0,
        ], $schema);
    }

    #[Test]
    public function it_sends_a_zero_discount_when_the_line_has_none(): void
    {
        $schema = self::schema(BillableItem::of('MAT-001', 'Matrícula 2026', Unit::service())->sold(
            quantity : Quantity::of(1),
            unitPrice: Amount::of('590.00'),
            tax      : IGVTreatment::taxedIncluded(),
        ));

        self::assertSame(0.0, $schema['descuento']);
    }

    #[Test]
    public function it_keeps_the_ten_decimals_of_the_unit_value(): void
    {
        $schema = self::schema(BillableItem::of('MAT-001', 'Matrícula 2026', Unit::service())->sold(
            quantity : Quantity::of(1),
            unitPrice: Amount::of('200.00'),
            tax      : IGVTreatment::taxedIncluded(),
            discount : Amount::of('100.00'),
        ));

        self::assertSame(169.4915254237, $schema['valor_unitario']);
        self::assertSame(84.75, $schema['descuento']);
        self::assertSame(100.0, $schema['total']);
    }

    #[Test]
    public function it_sends_the_sunat_product_code_only_when_present(): void
    {
        $withCode = self::schema(BillableItem::of('MAT-001', 'Matrícula 2026', Unit::service(), '86111604')->sold(
            quantity : Quantity::of(1),
            unitPrice: Amount::of('590.00'),
            tax      : IGVTreatment::exonerated(),
        ));

        self::assertSame('86111604', $withCode['codigo_producto_sunat']);
        self::assertSame(8, $withCode['tipo_de_igv']);
    }

    #[Test]
    public function it_sends_a_missing_internal_code_as_null(): void
    {
        $schema = self::schema(BillableItem::of(null, 'Matrícula 2026', Unit::product())->sold(
            quantity : Quantity::of(2),
            unitPrice: Amount::of('590.00'),
            tax      : IGVTreatment::taxedIncluded(),
        ));

        self::assertArrayHasKey('codigo', $schema);
        self::assertNull($schema['codigo']);
        self::assertArrayNotHasKey('codigo_producto_sunat', $schema);
        self::assertSame('NIU', $schema['unidad_de_medida']);
        self::assertSame(2, $schema['cantidad']);
    }

    #[Test]
    public function it_sends_the_igv_type_of_each_treatment(): void
    {
        $types = [];

        foreach ([IGVTreatment::taxedIncluded(), IGVTreatment::taxedExcluded(), IGVTreatment::exonerated(), IGVTreatment::unaffected()] as $treatment) {
            $types[] = self::schema(BillableItem::of('MAT-001', 'Matrícula 2026', Unit::service())->sold(
                quantity : Quantity::of(1),
                unitPrice: Amount::of('100.00'),
                tax      : $treatment,
            ))['tipo_de_igv'];
        }

        self::assertSame([1, 1, 8, 9], $types);
    }

    private static function schema(Item $item): array
    {
        return (new LineSchema(LineProjection::of($item, IgvRate::standard())))->value();
    }
}

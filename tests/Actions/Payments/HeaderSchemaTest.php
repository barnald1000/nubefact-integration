<?php

declare(strict_types=1);

namespace Savia\NubeFactIntegration\Tests\Actions\Payments;

use PHPUnit\Framework\Attributes\Test;
use Savia\NubeFactIntegration\Actions\Payments\HeaderSchema;
use Savia\NubeFactIntegration\Lines\Amount;
use Savia\NubeFactIntegration\Lines\IGVTreatment;
use Savia\NubeFactIntegration\Lines\Item;
use Savia\NubeFactIntegration\Lines\Line;
use Savia\NubeFactIntegration\Lines\Quantity;
use Savia\NubeFactIntegration\Lines\Unit;
use Savia\NubeFactIntegration\Projection\DocumentProjection;
use Savia\NubeFactIntegration\Tests\Support\LinesTransaction;
use Savia\NubeFactIntegration\Tests\TestCase;

final class HeaderSchemaTest extends TestCase
{
    #[Test]
    public function it_takes_identity_from_the_transaction_and_amounts_from_the_projection(): void
    {
        $header = (new HeaderSchema(DocumentProjection::of(new LinesTransaction(
            self::line('590.00', IGVTreatment::taxedIncluded(), '118.00'),
            self::line('100.00', IGVTreatment::exonerated()),
        ))))->value();

        self::assertSame(2, $header['tipo_de_comprobante']);
        self::assertSame('B001', $header['serie']);
        self::assertSame(1, $header['numero']);
        self::assertSame('12345678', $header['cliente_numero_de_documento']);
        self::assertSame('2026-10-11', $header['fecha_de_emision']);
        self::assertSame(1, $header['moneda']);
        self::assertSame(18.0, $header['porcentaje_de_igv']);
        self::assertSame(100.0, $header['total_descuento']);
        self::assertSame(400.0, $header['total_gravada']);
        self::assertSame(100.0, $header['total_exonerada']);
        self::assertSame(0.0, $header['total_inafecta']);
        self::assertSame(72.0, $header['total_igv']);
        self::assertSame(572.0, $header['total']);
    }

    #[Test]
    public function it_sends_every_header_field_nubefact_receives_today(): void
    {
        $header = (new HeaderSchema(DocumentProjection::of(new LinesTransaction(
            self::line('118.00', IGVTreatment::taxedIncluded()),
        ))))->value();

        self::assertSame([
            'tipo_de_comprobante'               => 2,
            'serie'                             => 'B001',
            'numero'                            => 1,
            'sunat_transaction'                 => 1,
            'cliente_tipo_de_documento'         => 1,
            'cliente_numero_de_documento'       => '12345678',
            'cliente_denominacion'              => 'Juan Pérez',
            'cliente_direccion'                 => null,
            'cliente_email'                     => null,
            'fecha_de_emision'                  => '2026-10-11',
            'moneda'                            => 1,
            'porcentaje_de_igv'                 => 18.0,
            'total_descuento'                   => 0.0,
            'total_gravada'                     => 100.0,
            'total_exonerada'                   => 0.0,
            'total_inafecta'                    => 0.0,
            'total_igv'                         => 18.0,
            'total'                             => 118.0,
            'enviar_automaticamente_a_la_sunat' => true,
            'enviar_automaticamente_al_cliente' => true,
        ], $header);
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

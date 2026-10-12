<?php
/**
 * @author enea dhack <contact@vaened.dev>
 * @link https://vaened.dev DevFolio
 */

declare(strict_types=1);

namespace Savia\NubeFactIntegration\Actions\Payments;

use Savia\NubeFactIntegration\DecimalFormat;
use Savia\NubeFactIntegration\Projection\DocumentProjection;
use Savia\NubeFactIntegration\Schema;

/**
 * Translates a projected document into the Nubefact header. Identity data comes from the transaction; every amount
 * comes from the projection of its operation.
 */
final class HeaderSchema extends Schema
{
    public function __construct(
        private readonly DocumentProjection $document
    ) {}

    protected function structure(): array
    {
        $entry     = $this->document->transaction();
        $operation = $this->document->operation();
        $amounts   = $this->document->projection();

        return [
            "tipo_de_comprobante"               => $entry->type()->value,
            "serie"                             => $entry->serial(),
            "numero"                            => $entry->number(),
            "sunat_transaction"                 => 1,
            "cliente_tipo_de_documento"         => $entry->customer()->identificationType()->value,
            "cliente_numero_de_documento"       => $entry->customer()->identificationNumber(),
            "cliente_denominacion"              => $entry->customer()->denomination(),
            "cliente_direccion"                 => $entry->customer()->address(),
            "cliente_email"                     => $entry->customer()->email(),
            "fecha_de_emision"                  => $entry->issueDate()->format('Y-m-d'),
            "moneda"                            => $operation->currency()->value,
            "porcentaje_de_igv"                 => DecimalFormat::number($operation->igvRate()->percentage(), 2),
            "total_descuento"                   => DecimalFormat::number($amounts->totalDiscounts(), 2),
            "total_gravada"                     => DecimalFormat::number($amounts->totalTaxed(), 2),
            "total_exonerada"                   => DecimalFormat::number($amounts->totalExonerated(), 2),
            "total_inafecta"                    => DecimalFormat::number($amounts->totalUnaffected(), 2),
            "total_igv"                         => DecimalFormat::number($amounts->totalTaxes(), 2),
            "total"                             => DecimalFormat::number($amounts->total(), 2),
            "enviar_automaticamente_a_la_sunat" => true,
            "enviar_automaticamente_al_cliente" => true,
        ];
    }

    protected static function required(): array
    {
        return [
            "tipo_de_comprobante",
            "serie",
            "numero",
            "sunat_transaction",
            "cliente_tipo_de_documento",
            "cliente_numero_de_documento",
            "cliente_denominacion",
            "cliente_direccion",
            "cliente_email",
            "fecha_de_emision",
            "moneda",
            "porcentaje_de_igv",
            "total_descuento",
            "total_gravada",
            "total_exonerada",
            "total_inafecta",
            "total_igv",
            "total",
        ];
    }
}

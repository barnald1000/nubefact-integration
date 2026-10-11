<?php
/**
 * @author enea dhack <contact@vaened.dev>
 * @link https://vaened.dev DevFolio
 */

declare(strict_types=1);

namespace Savia\NubeFactIntegration\Actions\Payments;

use Savia\NubeFactIntegration\DecimalFormat;
use Savia\NubeFactIntegration\Projection\LineProjection;
use Savia\NubeFactIntegration\Schema;

/**
 * Translates an already calculated line into a Nubefact item. It does not calculate anything.
 */
final class LineSchema extends Schema
{
    public function __construct(
        private readonly LineProjection $line
    ) {}

    protected function structure(): array
    {
        $item = $this->line->item();

        $structure = [
            "unidad_de_medida" => $item->unit()->code(),
            "codigo"           => $item->code(),
            "descripcion"      => $item->description(),
            "cantidad"         => $this->line->quantity()->value(),
            "valor_unitario"   => DecimalFormat::number($this->line->unitValue(), 10),
            "precio_unitario"  => DecimalFormat::number($this->line->unitPrice(), 10),
            "descuento"        => DecimalFormat::number($this->line->totalDiscounts(), 2),
            "subtotal"         => DecimalFormat::number($this->line->subtotal(), 2),
            "tipo_de_igv"      => $this->line->igvType()->value,
            "igv"              => DecimalFormat::number($this->line->totalTaxes(), 2),
            "total"            => DecimalFormat::number($this->line->total(), 2),
        ];

        if ($item->sunatCode() !== null) {
            $structure["codigo_producto_sunat"] = $item->sunatCode();
        }

        return $structure;
    }

    protected static function required(): array
    {
        return [
            "unidad_de_medida",
            "codigo",
            "descripcion",
            "cantidad",
            "valor_unitario",
            "precio_unitario",
            "descuento",
            "subtotal",
            "tipo_de_igv",
            "igv",
            "total",
        ];
    }
}

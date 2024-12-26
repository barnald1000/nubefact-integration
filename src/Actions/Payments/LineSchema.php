<?php
/**
 * @author enea dhack <contact@vaened.dev>
 * @link https://vaened.dev DevFolio
 */

declare(strict_types=1);

namespace Savia\NubeFact\Actions\Payments;

use Savia\NubeFact\Contracts\TransactionEntryItem;
use Savia\NubeFact\FinancialCalculator;
use Savia\NubeFact\Schema;

final class LineSchema extends Schema
{
    private readonly FinancialCalculator $calculator;

    public function __construct(
        private readonly TransactionEntryItem $item
    )
    {
        $this->calculator = new FinancialCalculator($item);
    }

    protected function structure(): array
    {
        return [
            "unidad_de_medida" => $this->item->measureCode(),
            "codigo"           => $this->item->code(),
            "descripcion"      => $this->item->description(),
            "cantidad"         => $this->item->quantity(),
            "valor_unitario"   => $this->createUnitaryValue(),
            "precio_unitario"  => $this->createUnitaryPrice(),
            "descuento"        => $this->calculator->totalDiscounts(),
            "subtotal"         => $this->calculator->netTotal(),
            "tipo_de_igv"      => $this->calculator->generateIVGType()->value,
            "igv"              => $this->calculator->totalTaxes(),
            "total"            => $this->calculator->total(),
        ];
    }

    private function createUnitaryValue(): float
    {
        return $this->calculator->netTotal() / $this->item->quantity();
    }

    private function createUnitaryPrice(): float
    {
        return $this->calculator->total() / $this->item->quantity();
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
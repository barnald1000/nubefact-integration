<?php

declare(strict_types=1);

namespace Savia\NubeFactIntegration;

use Savia\NubeFactIntegration\Keywords\Currency;
use Savia\NubeFactIntegration\Keywords\IgvRate;
use Savia\NubeFactIntegration\Lines\Items;
use Savia\NubeFactIntegration\Projection\OperationProjection;

/**
 * Operation
 *
 * What a document records: its items, the currency of their amounts and the IGV rate that applies to all of them.
 * The items and the rate only exist together, so an item is always calculated with the rate of its own operation.
 * An operation exists before its document, so its amounts can be calculated before the document is created.
 */
final readonly class Operation
{
    private function __construct(
        private Currency $currency,
        private IgvRate $igvRate,
        private Items $items,
    ) {}

    public static function of(Currency $currency, IgvRate $igvRate, Items $items): self
    {
        return new self($currency, $igvRate, $items);
    }

    public function currency(): Currency
    {
        return $this->currency;
    }

    public function igvRate(): IgvRate
    {
        return $this->igvRate;
    }

    public function items(): Items
    {
        return $this->items;
    }

    public function project(): OperationProjection
    {
        return OperationProjection::of($this);
    }
}

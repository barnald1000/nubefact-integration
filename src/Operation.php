<?php

declare(strict_types=1);

namespace Savia\NubeFactIntegration;

use Savia\NubeFactIntegration\Keywords\Currency;
use Savia\NubeFactIntegration\Keywords\IgvRate;
use Savia\NubeFactIntegration\Lines\Lines;
use Savia\NubeFactIntegration\Projection\OperationProjection;

/**
 * Operation
 *
 * What a document records: its lines, the currency of their amounts and the IGV rate that applies to all of them.
 * The lines and the rate only exist together, so a line is always calculated with the rate of its own operation.
 * An operation exists before its document, so its amounts can be calculated before the document is created.
 */
final readonly class Operation
{
    private function __construct(
        private Currency $currency,
        private IgvRate $igvRate,
        private Lines $lines,
    ) {}

    public static function of(Currency $currency, IgvRate $igvRate, Lines $lines): self
    {
        return new self($currency, $igvRate, $lines);
    }

    public function currency(): Currency
    {
        return $this->currency;
    }

    public function igvRate(): IgvRate
    {
        return $this->igvRate;
    }

    public function lines(): Lines
    {
        return $this->lines;
    }

    public function project(): OperationProjection
    {
        return OperationProjection::of($this);
    }
}

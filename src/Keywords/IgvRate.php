<?php

declare(strict_types=1);

namespace Savia\NubeFactIntegration\Keywords;

use Brick\Math\BigDecimal;

/**
 * The IGV rate selected by the fiscal document, not by an individual line.
 */
final readonly class IgvRate
{
    private function __construct(private BigDecimal $decimal) {}

    public static function standard(): self
    {
        return new self(BigDecimal::of('0.18'));
    }

    public function decimal(): BigDecimal
    {
        return $this->decimal;
    }

    public function factor(): BigDecimal
    {
        return BigDecimal::one()->plus($this->decimal);
    }

    public function percentage(): BigDecimal
    {
        return $this->decimal->multipliedBy(100);
    }
}

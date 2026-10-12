<?php

declare(strict_types=1);

namespace Savia\NubeFactIntegration\Keywords;

use Brick\Math\BigDecimal;
use Brick\Math\Exception\MathException;
use Savia\NubeFactIntegration\Errors\InvalidIgvRate;

/**
 * IGV rate
 *
 * The IGV rate of a document, chosen by the application as a percentage greater than 0 and up to 100, with at most
 * 2 decimals as Nubefact receives it in `porcentaje_de_igv`. `standard()` is the general 18 % rate.
 */
final readonly class IgvRate
{
    private function __construct(private BigDecimal $decimal) {}

    public static function standard(): self
    {
        return self::of(18);
    }

    public static function of(int|float $percentage): self
    {
        try {
            $value = BigDecimal::of((string) $percentage);
        } catch (MathException) {
            throw new InvalidIgvRate('La tasa de IGV debe ser un número finito.');
        }

        if ($value->isLessThanOrEqualTo(0) || $value->isGreaterThan(100)) {
            throw new InvalidIgvRate('La tasa de IGV debe ser mayor que 0 y como máximo 100.');
        }

        $scale = $value->strippedOfTrailingZeros()->getScale();

        if ($scale > 2) {
            throw new InvalidIgvRate('La tasa de IGV no puede tener más de 2 decimales.');
        }

        return new self($value->dividedBy(100, max($scale, 0) + 2));
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

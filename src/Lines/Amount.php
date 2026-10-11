<?php

declare(strict_types=1);

namespace Savia\NubeFactIntegration\Lines;

use Brick\Math\BigDecimal;
use Brick\Math\Exception\MathException;

/**
 * A commercial amount with at most two decimals. The document owns the currency, not the amount.
 */
final readonly class Amount
{
    private function __construct(private BigDecimal $value) {}

    public static function of(int|string $value): self
    {
        try {
            $decimal = BigDecimal::of($value);
        } catch (MathException) {
            throw new InvalidLine('El importe debe ser un número decimal.');
        }

        if ($decimal->isNegative()) {
            throw new InvalidLine('El importe no puede ser negativo.');
        }

        if ($decimal->strippedOfTrailingZeros()->getScale() > 2) {
            throw new InvalidLine('El importe no puede tener más de 2 decimales.');
        }

        return new self($decimal);
    }

    public function value(): BigDecimal
    {
        return $this->value;
    }
}

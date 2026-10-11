<?php

declare(strict_types=1);

namespace Savia\NubeFactIntegration\Lines;

/**
 * Quantity
 *
 * The number of units sold in a line. It is a whole number: the business sells monthly services and countable products,
 * and a fractional quantity is the only case where a line total could differ by a cent from its subtotal plus IGV.
 */
final readonly class Quantity
{
    private function __construct(private int $value) {}

    public static function of(int $value): self
    {
        if ($value <= 0) {
            throw new InvalidLine('La cantidad debe ser mayor que cero.');
        }

        return new self($value);
    }

    public function value(): int
    {
        return $this->value;
    }
}

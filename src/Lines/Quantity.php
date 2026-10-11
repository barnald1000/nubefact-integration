<?php

declare(strict_types=1);

namespace Savia\NubeFactIntegration\Lines;

use Brick\Math\BigDecimal;

use function rtrim;

final readonly class Quantity
{
    private function __construct(private BigDecimal $value) {}

    public static function of(int|string $value): self
    {
        $decimal = BigDecimal::of($value);

        if ($decimal->isLessThanOrEqualTo(0)) {
            throw new InvalidLine('La cantidad debe ser mayor que cero.');
        }

        if ($decimal->getScale() > 10) {
            throw new InvalidLine('La cantidad no puede exceder 10 decimales según Nubefact.');
        }

        return new self($decimal);
    }

    public function value(): BigDecimal
    {
        return $this->value;
    }

    public function format(): string
    {
        $value = (string) $this->value;

        return str_contains($value, '.') ? rtrim(rtrim($value, '0'), '.') : $value;
    }
}

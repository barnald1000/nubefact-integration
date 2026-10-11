<?php

declare(strict_types=1);

namespace Savia\NubeFactIntegration\Lines;

/**
 * One onerous Nubefact item. Its provider fields are derived by LineProjection.
 */
final readonly class Line
{
    private function __construct(
        private Item $item,
        private Quantity $quantity,
        private Amount $unitPrice,
        private IGVTreatment $tax,
        private ?Amount $discount,
    ) {}

    public static function onerous(
        Item $item,
        Quantity $quantity,
        Amount $unitPrice,
        IGVTreatment $tax,
        ?Amount $discount = null,
    ): self {
        if ($unitPrice->value()->isZero()) {
            throw new InvalidLine('El precio unitario debe ser mayor que cero.');
        }

        if ($discount !== null && $discount->value()->isGreaterThan($unitPrice->value()->multipliedBy($quantity->value()))) {
            throw new InvalidLine('El descuento no puede exceder el importe original de la línea.');
        }

        return new self($item, $quantity, $unitPrice, $tax, $discount);
    }

    public function item(): Item
    {
        return $this->item;
    }

    public function quantity(): Quantity
    {
        return $this->quantity;
    }

    public function unitPrice(): Amount
    {
        return $this->unitPrice;
    }

    public function tax(): IGVTreatment
    {
        return $this->tax;
    }

    public function discount(): ?Amount
    {
        return $this->discount;
    }
}

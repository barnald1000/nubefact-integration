<?php

declare(strict_types=1);

namespace Savia\NubeFactIntegration\Projection;

use Brick\Math\BigDecimal;
use Brick\Math\BigRational;
use Brick\Math\RoundingMode;
use Savia\NubeFactIntegration\Contracts\Totalizable;
use Savia\NubeFactIntegration\Keywords\IgvRate;
use Savia\NubeFactIntegration\Keywords\IGVType;
use Savia\NubeFactIntegration\Lines\Item;
use Savia\NubeFactIntegration\Lines\Line;
use Savia\NubeFactIntegration\Lines\Quantity;

/**
 * Line projection
 *
 * The calculated figures of a line. It can only be created by calculating it from a Line, so its figures cannot
 * contradict each other. Calculation is exact and each 2-decimal figure is rounded once. It is created by
 * OperationProjection with the rate of the line's operation; calling `of()` directly is internal to the library.
 *
 * Amounts that carry IGV are divided by the IGV factor to bring them before IGV, and only taxed lines add IGV on top
 * of the subtotal. With IGV included, the total is exactly what was charged; otherwise it is the rounded subtotal
 * plus the rounded IGV. The unit value (before IGV) and the unit price (with IGV, before the discount) keep up to
 * 10 decimals, as Nubefact allows.
 *
 * @example taxed with IGV included, unit price 200.00 and discount 100.00 at 18 %:
 *
 *     unit value 169.4915254237 · unit price 200.00 · discount 84.75 · subtotal 84.75 · IGV 15.25 · total 100.00
 */
final readonly class LineProjection implements Totalizable
{
    private function __construct(
        private Item $item,
        private Quantity $quantity,
        private BigDecimal $unitValue,
        private BigDecimal $unitPrice,
        private BigDecimal $discount,
        private BigDecimal $subtotal,
        private IGVType $igvType,
        private BigDecimal $tax,
        private BigDecimal $total,
    ) {}

    public static function of(Line $line, IgvRate $igvRate): self
    {
        $treatment = $line->tax();
        $quantity  = $line->quantity()->value();
        $price     = $line->unitPrice()->value();
        $discount  = $line->discount()?->value() ?? BigDecimal::zero();

        $base = $treatment->includesIgv() ? $igvRate->factor() : BigDecimal::one();
        $rate = $treatment->isTaxed() ? $igvRate->decimal() : BigDecimal::zero();

        $unitValue         = BigRational::of($price)->dividedBy($base);
        $discountBeforeIgv = BigRational::of($discount)->dividedBy($base);
        $subtotal          = $unitValue->multipliedBy($quantity)->minus($discountBeforeIgv);

        $roundedSubtotal = self::rounded($subtotal);
        $roundedTax      = self::rounded($subtotal->multipliedBy($rate));
        $roundedTotal    = $treatment->includesIgv()
            ? self::rounded(BigRational::of($price)->multipliedBy($quantity)->minus($discount))
            : $roundedSubtotal->plus($roundedTax);

        return new self(
            $line->item(),
            $line->quantity(),
            $unitValue->toScale(10, RoundingMode::HalfUp),
            $unitValue->multipliedBy(BigDecimal::one()->plus($rate))->toScale(10, RoundingMode::HalfUp),
            self::rounded($discountBeforeIgv),
            $roundedSubtotal,
            $treatment->toNubefact(),
            $roundedTax,
            $roundedTotal,
        );
    }

    public function igvType(): IGVType
    {
        return $this->igvType;
    }

    public function total(): BigDecimal
    {
        return $this->total;
    }

    public function subtotal(): BigDecimal
    {
        return $this->subtotal;
    }

    public function totalTaxes(): BigDecimal
    {
        return $this->tax;
    }

    public function totalDiscounts(): BigDecimal
    {
        return $this->discount;
    }

    public function item(): Item
    {
        return $this->item;
    }

    public function quantity(): Quantity
    {
        return $this->quantity;
    }

    public function unitValue(): BigDecimal
    {
        return $this->unitValue;
    }

    public function unitPrice(): BigDecimal
    {
        return $this->unitPrice;
    }

    private static function rounded(BigRational $value): BigDecimal
    {
        return $value->toScale(2, RoundingMode::HalfUp);
    }
}

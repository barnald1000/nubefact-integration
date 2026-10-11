<?php

declare(strict_types=1);

namespace Savia\NubeFactIntegration\Lines\Projection;

use Brick\Math\BigDecimal;
use Brick\Math\BigRational;
use Brick\Math\RoundingMode;
use Savia\NubeFactIntegration\Keywords\IgvRate;
use Savia\NubeFactIntegration\Keywords\IGVType;
use Savia\NubeFactIntegration\Lines\Item;
use Savia\NubeFactIntegration\Lines\Line;
use Savia\NubeFactIntegration\Lines\Quantity;

use function rtrim;
use function str_contains;

/**
 * A line seen through Nubefact's schema. It can only be created by calculating it from a Line, so its figures cannot
 * contradict each other. Calculation is exact; each 2-decimal figure is rounded once.
 */
final readonly class LineProjection
{
    private function __construct(
        private Item $item,
        private Quantity $quantity,
        private BigDecimal $unitValue,
        private BigDecimal $unitPrice,
        private ?BigDecimal $discount,
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

        // Amounts that carry IGV are divided by the IGV factor to get them before IGV.
        $base = $treatment->includesIgv() ? $igvRate->factor() : BigDecimal::one();
        // Only taxed lines add IGV on top of the subtotal.
        $rate = $treatment->isTaxed() ? $igvRate->decimal() : BigDecimal::zero();

        $unitValue         = BigRational::of($price)->dividedBy($base);
        $discountBeforeIgv = BigRational::of($discount)->dividedBy($base);
        $subtotal          = $unitValue->multipliedBy($quantity)->minus($discountBeforeIgv);

        $roundedSubtotal = self::rounded($subtotal);
        $roundedTax      = self::rounded($subtotal->multipliedBy($rate));
        // With IGV included the total is exactly what was charged; otherwise it is the subtotal plus the IGV.
        $roundedTotal = $treatment->includesIgv()
            ? self::rounded(BigRational::of($price)->multipliedBy($quantity)->minus($discount))
            : $roundedSubtotal->plus($roundedTax);

        return new self(
            $line->item(),
            $line->quantity(),
            $unitValue->toScale(10, RoundingMode::HalfUp),
            $unitValue->multipliedBy(BigDecimal::one()->plus($rate))->toScale(10, RoundingMode::HalfUp),
            $line->discount() === null ? null : self::rounded($discountBeforeIgv),
            $roundedSubtotal,
            $treatment->toNubefact(),
            $roundedTax,
            $roundedTotal,
        );
    }

    public function total(): BigDecimal
    {
        return $this->total;
    }

    public function subtotal(): BigDecimal
    {
        return $this->subtotal;
    }

    public function tax(): BigDecimal
    {
        return $this->tax;
    }

    public function discount(): ?BigDecimal
    {
        return $this->discount;
    }

    public function toNubefact(): array
    {
        $value = [
            'unidad_de_medida' => $this->item->unit()->code(),
            'descripcion'      => $this->item->description(),
            'cantidad'         => $this->quantity->format(),
            'valor_unitario'   => self::trimmed($this->unitValue, 10),
            'precio_unitario'  => self::trimmed($this->unitPrice, 10),
            'subtotal'         => self::trimmed($this->subtotal, 2),
            'tipo_de_igv'      => $this->igvType->value,
            'igv'              => self::trimmed($this->tax, 2),
            'total'            => self::trimmed($this->total, 2),
        ];

        if ($this->item->code() !== null) {
            $value['codigo'] = $this->item->code();
        }
        if ($this->discount !== null) {
            $value['descuento'] = self::trimmed($this->discount, 2);
        }
        if ($this->item->sunatCode() !== null) {
            $value['codigo_producto_sunat'] = $this->item->sunatCode();
        }

        return $value;
    }

    private static function rounded(BigRational $value): BigDecimal
    {
        return $value->toScale(2, RoundingMode::HalfUp);
    }

    private static function trimmed(BigDecimal $value, int $scale): string
    {
        $text = (string) $value->toScale($scale, RoundingMode::HalfUp);

        return str_contains($text, '.') ? rtrim(rtrim($text, '0'), '.') : $text;
    }
}

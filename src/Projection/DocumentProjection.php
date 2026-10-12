<?php

declare(strict_types=1);

namespace Savia\NubeFactIntegration\Projection;

use Brick\Math\BigDecimal;
use LogicException;
use Savia\NubeFactIntegration\Contracts\FinancialTransaction;
use Savia\NubeFactIntegration\Contracts\Totalizable;
use Savia\NubeFactIntegration\Keywords\IgvRate;
use Savia\NubeFactIntegration\Keywords\IGVType;
use Savia\NubeFactIntegration\Lines\Line;

/**
 * The calculated figures of a document: its calculated lines and the header totals. The header totals are the sum of
 * the already rounded figures of each line, so the header always matches the items sent to Nubefact. The subtotal is
 * the sum of every line subtotal, and taxed, exonerated and unaffected split that same subtotal by IGV type.
 */
final readonly class DocumentProjection implements Totalizable
{
    private function __construct(
        private FinancialTransaction $transaction,
        private LineProjections $lines,
        private BigDecimal $subtotal,
        private BigDecimal $totalTaxed,
        private BigDecimal $totalExonerated,
        private BigDecimal $totalUnaffected,
        private BigDecimal $totalDiscounts,
        private BigDecimal $totalTaxes,
        private BigDecimal $total,
    ) {}

    public static function of(FinancialTransaction $transaction): self
    {
        $igvRate = $transaction->igvRate();
        $lines   = new LineProjections(
            $transaction->lines()->map(static fn(Line $line) => LineProjection::of($line, $igvRate))->values(),
        );

        $subtotal = $taxed = $exonerated = $unaffected = $discount = $igv = $total = BigDecimal::zero()->toScale(2);

        foreach ($lines->items() as $line) {
            $type = $line->igvType();

            match ($type) {
                IGVType::GravadoOnerous    => $taxed      = $taxed->plus($line->subtotal()),
                IGVType::ExoneratedOnerous => $exonerated = $exonerated->plus($line->subtotal()),
                IGVType::ExemptOnerous     => $unaffected = $unaffected->plus($line->subtotal()),
                default                    => throw new LogicException("The IGV type $type->name has no header total."),
            };

            $subtotal = $subtotal->plus($line->subtotal());
            $discount = $discount->plus($line->totalDiscounts());
            $igv      = $igv->plus($line->totalTaxes());
            $total    = $total->plus($line->total());
        }

        return new self($transaction, $lines, $subtotal, $taxed, $exonerated, $unaffected, $discount, $igv, $total);
    }

    public function transaction(): FinancialTransaction
    {
        return $this->transaction;
    }

    public function igvRate(): IgvRate
    {
        return $this->transaction->igvRate();
    }

    public function lines(): LineProjections
    {
        return $this->lines;
    }

    public function subtotal(): BigDecimal
    {
        return $this->subtotal;
    }

    public function totalTaxed(): BigDecimal
    {
        return $this->totalTaxed;
    }

    public function totalExonerated(): BigDecimal
    {
        return $this->totalExonerated;
    }

    public function totalUnaffected(): BigDecimal
    {
        return $this->totalUnaffected;
    }

    public function totalDiscounts(): BigDecimal
    {
        return $this->totalDiscounts;
    }

    public function totalTaxes(): BigDecimal
    {
        return $this->totalTaxes;
    }

    public function total(): BigDecimal
    {
        return $this->total;
    }
}

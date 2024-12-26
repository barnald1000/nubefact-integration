<?php
/**
 * @author enea dhack <contact@vaened.dev>
 * @link https://vaened.dev DevFolio
 */

declare(strict_types=1);

namespace Savia\NubeFact;

use Savia\NubeFact\Contracts\FinancialTransaction;

final readonly class TransactionSummarizer
{
    public function __construct(private FinancialTransaction $transaction)
    {
    }

    /**
     * Calculates the "Total Gravado" (Taxable Total) of the entity.
     *
     * The "Total Gravado" represents the total amount of operations that are subject
     * to tax payment. This value includes the price of the products or services plus
     * the corresponding taxes.
     *
     * @return float The "Total Gravado" (Taxable Total) of the entity.
     */
    public function taxableTotal(): float
    {
        return $this->transaction->total();
    }

    /**
     * Calculates the "Total Infacto" (Non-Taxable Total) of the entity.
     *
     * The "Total Infacto" represents the total amount of operations that are not
     * subject to tax payment. This value includes exempted or non-taxable items.
     *
     * @return float The "Total Infacto" (Non-Taxable Total) of the entity.
     */
    public function nonTaxableTotal(): float
    {
        return $this->transaction->total() - $this->taxableTotal();
    }

    public function totalTaxes(): float
    {
        return $this->transaction->totalTaxes();
    }

    public function total(): float
    {
        return $this->transaction->total();
    }
}
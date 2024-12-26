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
     * The "Total Gravado" represents the total amount of the sale or service, before
     * the application of any taxes. This value serves as the base for calculating the
     * corresponding tax amount (such as the IGV or IVA tax).
     *
     * The "Total Gravado" does not include the tax amount itself, but only the base
     * value of the transaction that is subject to taxation.
     *
     * @return float The "Total Gravado" (Taxable Total) of the entity.
     */
    public function taxableTotal(): float
    {
        return $this->transaction->netTotal();
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

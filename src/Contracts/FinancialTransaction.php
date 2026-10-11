<?php
/**
 * @author enea dhack <contact@vaened.dev>
 * @link https://vaened.dev DevFolio
 */

declare(strict_types=1);

namespace Savia\NubeFactIntegration\Contracts;

use DateTimeInterface;
use Savia\NubeFactIntegration\Keywords\Currency;
use Savia\NubeFactIntegration\Keywords\DocumentType;
use Savia\NubeFactIntegration\Keywords\IgvRate;
use Savia\NubeFactIntegration\Lines\Lines;

/**
 * Interface FinancialTransaction
 *
 * Represents a financial transaction document, such as an invoice, receipt, credit note, or debit note.
 * It only provides facts: identity data and commercial lines. Every amount is calculated by the library.
 */
interface FinancialTransaction
{
    public function id(): string;

    public function number(): int;

    public function serial(): string;

    public function type(): DocumentType;

    public function igvRate(): IgvRate;

    public function customer(): Customer;

    public function issueDate(): DateTimeInterface;

    public function currency(): Currency;

    public function lines(): Lines;
}

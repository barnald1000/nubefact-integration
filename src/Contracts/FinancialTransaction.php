<?php
/**
 * @author enea dhack <contact@vaened.dev>
 * @link https://vaened.dev DevFolio
 */

declare(strict_types=1);

namespace Savia\NubeFact\Contracts;

use DateTimeInterface;
use Savia\NubeFact\Currency;
use Savia\NubeFact\DocumentType;
use Savia\NubeFact\Items;

/**
 * Interface FinancialTransaction
 *
 * Represents a financial transaction document, such as an invoice, receipt, credit note, or debit note.
 * This abstraction provides a standardized structure to manage and track financial records across
 * various transaction types in a consistent and reusable way.
 */
interface FinancialTransaction extends Totalizable
{
    public function number(): int;

    public function serial(): string;

    public function type(): DocumentType;

    public function customer(): Customer;

    public function issueDate(): DateTimeInterface;

    public function currency(): Currency;

    public function items(): Items;
}
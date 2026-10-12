<?php
/**
 * @author enea dhack <contact@vaened.dev>
 * @link https://vaened.dev DevFolio
 */

declare(strict_types=1);

namespace Savia\NubeFactIntegration\Contracts;

use DateTimeInterface;
use Savia\NubeFactIntegration\Keywords\DocumentType;
use Savia\NubeFactIntegration\Operation;

/**
 * Interface FinancialTransaction
 *
 * Represents a financial transaction document, such as an invoice, receipt, credit note, or debit note.
 * It provides the identity of the document and the operation it records. Every amount is calculated by the library
 * from that operation.
 */
interface FinancialTransaction
{
    public function id(): string;

    public function number(): int;

    public function serial(): string;

    public function type(): DocumentType;

    public function customer(): Customer;

    public function issueDate(): DateTimeInterface;

    public function operation(): Operation;
}

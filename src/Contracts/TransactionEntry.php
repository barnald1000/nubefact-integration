<?php
/**
 * @author enea dhack <contact@vaened.dev>
 * @link https://vaened.dev DevFolio
 */

declare(strict_types=1);

namespace Savia\NubeFact\Contracts;

/**
 * TransactionEntryItem
 *
 * Represents a transaction document, such as an invoice or receipt.
 * This abstraction provides a consistent structure for recording financial exchanges,
 * ensuring clarity and uniformity across different transaction types.
 */
interface TransactionEntry extends FinancialTransaction
{
}
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
 * Defines the structure for item-related entities in a transaction entry.
 * Represents individual items such as products or services, ensuring consistency in item-related data.
 */
interface TransactionEntryItem extends Totalizable
{
    public function code(): ?string;

    public function description(): string;

    public function quantity(): int;

    public function measureCode(): string;

    public function unitaryPrice(): float;
}
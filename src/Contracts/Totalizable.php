<?php
/**
 * @author enea dhack <contact@vaened.dev>
 * @link https://vaened.dev DevFolio
 */

declare(strict_types=1);

namespace Savia\NubeFactIntegration\Contracts;

/**
 * Totalizable
 *
 * Represents entities capable of providing total-related financial calculations.
 * This interface is designed for use in financial contexts where detailed breakdowns
 * of totals are required, such as invoices, receipts, or other transaction records.
 *
 * Key responsibilities of this interface:
 * - Provide the **net total**, which is the subtotal before taxes and discounts.
 * - Provide the **discount total**, detailing the cumulative value of all discounts.
 * - Provide the **tax total**, showing the sum of all applicable taxes.
 * - Provide the **total**, representing the final payable amount after calculations.
 *
 * Example Use Case:
 * An invoice can implement `Totalizable` to encapsulate its financial details.
 * This ensures that all relevant calculations like taxes, discounts, and final payable
 * amounts are standardized and consistently accessible.
 */
interface Totalizable
{
    public function total(): float;

    public function netTotal(): float;

    public function totalDiscounts(): float;

    public function totalTaxes(): float;
}
<?php
/**
 * @author enea dhack <contact@vaened.dev>
 * @link https://vaened.dev DevFolio
 */

declare(strict_types=1);

namespace Savia\NubeFactIntegration\Contracts;

use Brick\Math\BigDecimal;

/**
 * Totalizable
 *
 * Represents entities capable of providing total-related financial calculations.
 * This interface is designed for use in financial contexts where detailed breakdowns
 * of totals are required, such as invoices, receipts, or other transaction records.
 *
 * Key responsibilities of this interface:
 * - Provide the **subtotal**, the amount before taxes: a line's sale value, or the sum of every line subtotal.
 * - Provide the **discount total**, detailing the cumulative value of all discounts.
 * - Provide the **tax total**, showing the sum of all applicable taxes.
 * - Provide the **total**, representing the final payable amount after calculations.
 */
interface Totalizable
{
    public function subtotal(): BigDecimal;

    public function total(): BigDecimal;

    public function totalDiscounts(): BigDecimal;

    public function totalTaxes(): BigDecimal;
}

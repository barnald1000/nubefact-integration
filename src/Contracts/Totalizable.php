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
 * It only exposes the totals that a line and a document share. The net amount is left out on purpose:
 * a line has one subtotal, while a document splits it into taxed, exonerated and unaffected bases.
 *
 * Key responsibilities of this interface:
 * - Provide the **discount total**, detailing the cumulative value of all discounts.
 * - Provide the **tax total**, showing the sum of all applicable taxes.
 * - Provide the **total**, representing the final payable amount after calculations.
 */
interface Totalizable
{
    public function total(): BigDecimal;

    public function totalDiscounts(): BigDecimal;

    public function totalTaxes(): BigDecimal;
}

<?php
/**
 * @author enea dhack <contact@vaened.dev>
 * @link https://vaened.dev DevFolio
 */

declare(strict_types=1);

namespace Savia\NubeFactIntegration\Contracts;

use Savia\NubeFactIntegration\Lines\Line;
use Savia\NubeFactIntegration\Lines\Unit;

/**
 * Item
 *
 * Represents one detail of a document as the application knows it: what is sold and the line with its amounts.
 * The library calculates each item from its own line and returns the same item with its results, so a result can
 * never be attributed to another item.
 *
 * Key responsibilities of this interface:
 * - Provide the **code**, optional, the application's internal code for what is sold.
 * - Provide the **description** and the **unit** of measure as they appear on the document.
 * - Provide the **SUNAT code**, optional, the product code of the SUNAT catalog.
 * - Provide the **line**, the quantity, price, IGV treatment and discount of the item. It is read once per
 *   calculation.
 */
interface Item
{
    public function code(): ?string;

    public function description(): string;

    public function unit(): Unit;

    public function sunatCode(): ?string;

    public function line(): Line;
}

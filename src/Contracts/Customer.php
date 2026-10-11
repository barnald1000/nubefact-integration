<?php
/**
 * @author enea dhack <contact@vaened.dev>
 * @link https://vaened.dev DevFolio
 */

declare(strict_types=1);

namespace Savia\NubeFactIntegration\Contracts;

/**
 * Customer
 *
 * Represents the buyer a document is issued to.
 *
 * Key responsibilities of this interface:
 * - Provide the **denomination**, the customer's name or business name as it appears on the document.
 * - Provide the **email**, optional, where the document can be sent; null when the customer has none.
 * - Provide the **address**, optional; null when the customer has none.
 */
interface Customer extends Identifiable
{
    public function denomination(): string;

    public function email(): ?string;

    public function address(): ?string;
}

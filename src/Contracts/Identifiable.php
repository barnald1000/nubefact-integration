<?php
/**
 * @author enea dhack <contact@vaened.dev>
 * @link https://vaened.dev DevFolio
 */

declare(strict_types=1);

namespace Savia\NubeFactIntegration\Contracts;

use Savia\NubeFactIntegration\Keywords\IdentificationType;

/**
 * Identifiable
 *
 * Represents anyone who can be identified by an official identity document.
 *
 * Key responsibilities of this interface:
 * - Provide the **identification type**, such as DNI, RUC, foreigner card or passport.
 * - Provide the **identification number**, the unique identifier issued for that document type.
 */
interface Identifiable
{
    public function identificationNumber(): string;

    public function identificationType(): IdentificationType;
}

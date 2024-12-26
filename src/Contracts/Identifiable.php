<?php
/**
 * @author enea dhack <contact@vaened.dev>
 * @link https://vaened.dev DevFolio
 */

declare(strict_types=1);

namespace Savia\NubeFactIntegration\Contracts;

use Savia\NubeFactIntegration\Keywords\IdentificationType;

interface Identifiable
{
    /**
     * Returns the identification number.
     *
     * This method should provide the unique identifier (such as a DNI, passport number, etc.)
     * associated with the implementing class.
     *
     * @return string The identification number.
     */
    public function identificationNumber(): string;

    /**
     * Returns the type of identification.
     *
     * This method defines the type of identification, such as "Passport", "Dni", "Ruc", etc.
     *
     * @return IdentificationType The type of identification.
     */
    public function identificationType(): IdentificationType;
}
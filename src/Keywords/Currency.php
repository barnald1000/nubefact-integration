<?php
/**
 * @author enea dhack <contact@vaened.dev>
 * @link https://vaened.dev DevFolio
 */

declare(strict_types=1);

namespace Savia\NubeFactIntegration\Keywords;

enum Currency: int
{
    case EUR = 3;

    case USD = 2;

    case PEN = 1;

    /**
     * ISO 4217 code used by monetary value objects.
     */
    public function code(): string
    {
        return match ($this) {
            self::EUR => 'EUR',
            self::USD => 'USD',
            self::PEN => 'PEN',
        };
    }
}

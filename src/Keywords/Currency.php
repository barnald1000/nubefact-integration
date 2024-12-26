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
}
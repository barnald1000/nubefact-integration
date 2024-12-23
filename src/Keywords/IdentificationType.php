<?php
/**
 * @author enea dhack <contact@vaened.dev>
 * @link https://vaened.dev DevFolio
 */

declare(strict_types=1);

namespace Savia\NubeFact;

enum IdentificationType: int
{
    case Dni = 1;

    case Ruc = 6;

    case Passport = 7;
}

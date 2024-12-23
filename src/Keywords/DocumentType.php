<?php
/**
 * @author enea dhack <contact@vaened.dev>
 * @link https://vaened.dev DevFolio
 */

declare(strict_types=1);

namespace Savia\NubeFact;

enum DocumentType: int
{
    case Invoice = 1;

    case Receipt = 2;

    case CreditNote = 3;

    case DebitNote = 4;
}

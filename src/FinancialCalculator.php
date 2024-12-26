<?php
/**
 * @author enea dhack <contact@vaened.dev>
 * @link https://vaened.dev DevFolio
 */

declare(strict_types=1);

namespace Savia\NubeFact;

use Savia\NubeFact\Contracts\Totalizable;
use Savia\NubeFact\Keywords\IGVType;

final readonly class FinancialCalculator
{
    public function __construct(
        private Totalizable $target
    )
    {
    }

    public function generateIVGType(): IGVType
    {
        return IGVType::GravadoOnerous;
    }
}
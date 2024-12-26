<?php
/**
 * @author enea dhack <contact@vaened.dev>
 * @link https://vaened.dev DevFolio
 */

declare(strict_types=1);

namespace Savia\NubeFact;

use Savia\NubeFact\Contracts\Totalizable;

final readonly class FinancialCalculator
{
    public function __construct(
        private Totalizable $target
    )
    {
    }

    public function total(): float
    {
        return $this->target->total();
    }

    public function netTotal(): float
    {
        return $this->target->netTotal();
    }

    public function totalDiscounts(): float
    {
        return $this->target->totalDiscounts();
    }

    public function totalTaxes(): float
    {
        return $this->target->totalTaxes();
    }

    public function generateIVGType(): IGVType
    {
        return IGVType::GravadoOnerous;
    }
}
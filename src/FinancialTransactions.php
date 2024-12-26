<?php
/**
 * @author enea dhack <contact@vaened.dev>
 * @link https://vaened.dev DevFolio
 */

declare(strict_types=1);

namespace Savia\NubeFact;

use Savia\NubeFact\Contracts\FinancialTransaction;
use Vaened\Support\Types\SecureList;

final class FinancialTransactions extends SecureList
{
    static public function type(): string
    {
        return FinancialTransaction::class;
    }
}
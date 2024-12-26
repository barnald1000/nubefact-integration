<?php
/**
 * @author enea dhack <contact@vaened.dev>
 * @link https://vaened.dev DevFolio
 */

declare(strict_types=1);

namespace Savia\NubeFact;

use Psr\Http\Message\ResponseInterface;
use Savia\NubeFact\Contracts\FinancialTransaction;

interface Sender
{
    public function send(FinancialTransaction $transaction): ResponseInterface;
}
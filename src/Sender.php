<?php
/**
 * @author enea dhack <contact@vaened.dev>
 * @link https://vaened.dev DevFolio
 */

declare(strict_types=1);

namespace Savia\NubeFactIntegration;

use Psr\Http\Message\ResponseInterface;
use Savia\NubeFactIntegration\Contracts\FinancialTransaction;

interface Sender
{
    public function send(FinancialTransaction $transaction): ResponseInterface;
}
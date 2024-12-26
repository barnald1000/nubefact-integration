<?php
/**
 * @author enea dhack <contact@vaened.dev>
 * @link https://vaened.dev DevFolio
 */

declare(strict_types=1);

namespace Savia\NubeFactIntegration;

use Psr\Http\Message\ResponseInterface;
use Savia\NubeFactIntegration\Keywords\Operation;

interface NubeFactService
{
    public function call(Operation $operation, array $data): ResponseInterface;
}
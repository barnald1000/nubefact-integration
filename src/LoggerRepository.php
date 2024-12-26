<?php
/**
 * @author enea dhack <contact@vaened.dev>
 * @link https://vaened.dev DevFolio
 */

declare(strict_types=1);

namespace Savia\NubeFactIntegration;

use Savia\NubeFactIntegration\Responses\Error;
use Savia\NubeFactIntegration\Responses\Success;

interface LoggerRepository
{
    public function log(Success|Error $response): void;
}
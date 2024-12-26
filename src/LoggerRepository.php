<?php
/**
 * @author enea dhack <contact@vaened.dev>
 * @link https://vaened.dev DevFolio
 */

declare(strict_types=1);

namespace Savia\NubeFact;

use Savia\NubeFact\Responses\Error;
use Savia\NubeFact\Responses\Success;

interface LoggerRepository
{
    public function log(Success|Error $response): void;
}
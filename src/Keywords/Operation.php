<?php
/**
 * @author enea dhack <contact@vaened.dev>
 * @link https://vaened.dev DevFolio
 */

declare(strict_types=1);

namespace Savia\NubeFactIntegration\Keywords;

enum Operation: string
{
    case GenerateDocument = 'generar_comprobante';
}

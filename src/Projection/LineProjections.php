<?php

declare(strict_types=1);

namespace Savia\NubeFactIntegration\Projection;

use Vaened\Support\Types\SecureList;

final class LineProjections extends SecureList
{
    public static function type(): string
    {
        return LineProjection::class;
    }
}

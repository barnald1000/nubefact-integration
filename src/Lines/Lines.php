<?php

declare(strict_types=1);

namespace Savia\NubeFactIntegration\Lines;

use Vaened\Support\Types\SecureList;

final class Lines extends SecureList
{
    public static function type(): string
    {
        return Line::class;
    }
}

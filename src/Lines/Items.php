<?php

declare(strict_types=1);

namespace Savia\NubeFactIntegration\Lines;

use Savia\NubeFactIntegration\Contracts\Item;
use Vaened\Support\Types\SecureList;

final class Items extends SecureList
{
    public static function type(): string
    {
        return Item::class;
    }
}

<?php
/**
 * @author enea dhack <contact@vaened.dev>
 * @link https://vaened.dev DevFolio
 */

declare(strict_types=1);

namespace Savia\NubeFactIntegration;

use Savia\NubeFactIntegration\Contracts\TransactionEntryItem;
use Vaened\Support\Types\SecureList;

final class Items extends SecureList
{
    static public function type(): string
    {
        return TransactionEntryItem::class;
    }
}
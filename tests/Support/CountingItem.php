<?php

declare(strict_types=1);

namespace Savia\NubeFactIntegration\Tests\Support;

use Savia\NubeFactIntegration\Contracts\Item;
use Savia\NubeFactIntegration\Lines\Line;
use Savia\NubeFactIntegration\Lines\Unit;

/**
 * Counts how many times its line is read.
 */
final class CountingItem implements Item
{
    public int $lineCalls = 0;

    public function __construct(private readonly Item $item) {}

    public function code(): ?string
    {
        return $this->item->code();
    }

    public function description(): string
    {
        return $this->item->description();
    }

    public function unit(): Unit
    {
        return $this->item->unit();
    }

    public function sunatCode(): ?string
    {
        return $this->item->sunatCode();
    }

    public function line(): Line
    {
        ++$this->lineCalls;

        return $this->item->line();
    }
}

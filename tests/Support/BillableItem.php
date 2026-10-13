<?php

declare(strict_types=1);

namespace Savia\NubeFactIntegration\Tests\Support;

use LogicException;
use Savia\NubeFactIntegration\Contracts\Item;
use Savia\NubeFactIntegration\Lines\Amount;
use Savia\NubeFactIntegration\Lines\IGVTreatment;
use Savia\NubeFactIntegration\Lines\Line;
use Savia\NubeFactIntegration\Lines\Quantity;
use Savia\NubeFactIntegration\Lines\Unit;

/**
 * An application item: described with `of()` and sold with `sold()`.
 */
final readonly class BillableItem implements Item
{
    private function __construct(
        private ?string $code,
        private string $description,
        private Unit $unit,
        private ?string $sunatCode,
        private ?Line $line,
    ) {}

    public static function of(?string $code, string $description, Unit $unit, ?string $sunatCode = null): self
    {
        return new self($code, $description, $unit, $sunatCode, null);
    }

    public function sold(Quantity $quantity, Amount $unitPrice, IGVTreatment $tax, ?Amount $discount = null): self
    {
        return new self($this->code, $this->description, $this->unit, $this->sunatCode, Line::onerous($quantity, $unitPrice, $tax, $discount));
    }

    public function code(): ?string
    {
        return $this->code;
    }

    public function description(): string
    {
        return $this->description;
    }

    public function unit(): Unit
    {
        return $this->unit;
    }

    public function sunatCode(): ?string
    {
        return $this->sunatCode;
    }

    public function line(): Line
    {
        return $this->line ?? throw new LogicException("The item $this->description was not sold.");
    }
}

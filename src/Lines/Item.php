<?php

declare(strict_types=1);

namespace Savia\NubeFactIntegration\Lines;

final readonly class Item
{
    private function __construct(
        private ?string $code,
        private string $description,
        private Unit $unit,
        private ?string $sunatCode,
    ) {}

    public static function of(?string $code, string $description, Unit $unit, ?string $sunatCode = null): self
    {
        return new self($code, $description, $unit, $sunatCode);
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
}

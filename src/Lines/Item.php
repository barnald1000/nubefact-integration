<?php

declare(strict_types=1);

namespace Savia\NubeFactIntegration\Lines;

use function strlen;

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
        if ($description === '' || strlen($description) > 250) {
            throw new InvalidLine('La descripción del ítem debe tener entre 1 y 250 caracteres.');
        }

        if ($code !== null && ($code === '' || strlen($code) > 250)) {
            throw new InvalidLine('El código interno del ítem debe tener entre 1 y 250 caracteres cuando se informe.');
        }

        if ($sunatCode !== null && ($sunatCode === '' || strlen($sunatCode) > 8)) {
            throw new InvalidLine('El código de producto SUNAT debe tener entre 1 y 8 caracteres cuando se informe.');
        }

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

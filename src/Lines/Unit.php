<?php

declare(strict_types=1);

namespace Savia\NubeFactIntegration\Lines;

use function preg_match;
use function strlen;
use function strtoupper;

final readonly class Unit
{
    private function __construct(private string $code) {}

    public static function product(): self
    {
        return new self('NIU');
    }

    public static function service(): self
    {
        return new self('ZZ');
    }

    public static function of(string $code): self
    {
        $code = strtoupper($code);

        if (strlen($code) < 2 || strlen($code) > 5 || preg_match('/^[A-Z0-9]+$/', $code) !== 1) {
            throw new InvalidLine('La unidad de medida debe tener entre 2 y 5 caracteres alfanuméricos.');
        }

        return new self($code);
    }

    public function code(): string
    {
        return $this->code;
    }
}

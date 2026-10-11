<?php

declare(strict_types=1);

namespace Savia\NubeFactIntegration\Lines;

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
        return new self($code);
    }

    public function code(): string
    {
        return $this->code;
    }
}

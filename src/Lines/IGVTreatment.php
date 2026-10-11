<?php

declare(strict_types=1);

namespace Savia\NubeFactIntegration\Lines;

use Savia\NubeFactIntegration\Keywords\IGVType;

/**
 * IGV treatment of an onerous line and the base of its amounts. It does not contain the IGV rate.
 */
final readonly class IGVTreatment
{
    private const TAXED_INCLUDED = 'taxed_included';

    private const TAXED_EXCLUDED = 'taxed_excluded';

    private const EXONERATED = 'exonerated';

    private const UNAFFECTED = 'unaffected';

    private function __construct(private string $key) {}

    public static function taxedIncluded(): self
    {
        return new self(self::TAXED_INCLUDED);
    }

    public static function taxedExcluded(): self
    {
        return new self(self::TAXED_EXCLUDED);
    }

    public static function exonerated(): self
    {
        return new self(self::EXONERATED);
    }

    public static function unaffected(): self
    {
        return new self(self::UNAFFECTED);
    }

    public static function fromKey(string $key): self
    {
        return match ($key) {
            self::TAXED_INCLUDED => self::taxedIncluded(),
            self::TAXED_EXCLUDED => self::taxedExcluded(),
            self::EXONERATED     => self::exonerated(),
            self::UNAFFECTED     => self::unaffected(),
            default              => throw new InvalidLine("Tratamiento de IGV desconocido: $key."),
        };
    }

    public function key(): string
    {
        return $this->key;
    }

    public function isTaxed(): bool
    {
        return $this->key === self::TAXED_INCLUDED || $this->key === self::TAXED_EXCLUDED;
    }

    public function includesIgv(): bool
    {
        return $this->key === self::TAXED_INCLUDED;
    }

    public function toNubefact(): IGVType
    {
        return match ($this->key) {
            self::TAXED_INCLUDED,
            self::TAXED_EXCLUDED => IGVType::GravadoOnerous,
            self::EXONERATED     => IGVType::ExoneratedOnerous,
            self::UNAFFECTED     => IGVType::ExemptOnerous,
        };
    }
}

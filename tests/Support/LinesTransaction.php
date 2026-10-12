<?php

declare(strict_types=1);

namespace Savia\NubeFactIntegration\Tests\Support;

use DateTimeImmutable;
use DateTimeInterface;
use Savia\NubeFactIntegration\Contracts\Customer;
use Savia\NubeFactIntegration\Contracts\FinancialTransaction;
use Savia\NubeFactIntegration\Keywords\Currency;
use Savia\NubeFactIntegration\Keywords\DocumentType;
use Savia\NubeFactIntegration\Keywords\IdentificationType;
use Savia\NubeFactIntegration\Keywords\IgvRate;
use Savia\NubeFactIntegration\Lines\Line;
use Savia\NubeFactIntegration\Lines\Lines;
use Savia\NubeFactIntegration\Operation;

/**
 * A receipt built from the given lines.
 */
final readonly class LinesTransaction implements FinancialTransaction
{
    private Lines $lines;

    public function __construct(Line ...$lines)
    {
        $this->lines = new Lines($lines);
    }

    public function id(): string
    {
        return 'document-1';
    }

    public function number(): int
    {
        return 1;
    }

    public function serial(): string
    {
        return 'B001';
    }

    public function type(): DocumentType
    {
        return DocumentType::Receipt;
    }

    public function customer(): Customer
    {
        return new GenericClient('Juan Pérez', null, null, '12345678', IdentificationType::Dni);
    }

    public function issueDate(): DateTimeInterface
    {
        return new DateTimeImmutable('2026-10-11');
    }

    public function operation(): Operation
    {
        return Operation::of(Currency::PEN, IgvRate::standard(), $this->lines);
    }
}

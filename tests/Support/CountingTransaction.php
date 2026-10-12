<?php

declare(strict_types=1);

namespace Savia\NubeFactIntegration\Tests\Support;

use DateTimeInterface;
use Savia\NubeFactIntegration\Contracts\Customer;
use Savia\NubeFactIntegration\Contracts\FinancialTransaction;
use Savia\NubeFactIntegration\Keywords\DocumentType;
use Savia\NubeFactIntegration\Operation;

/**
 * A transaction that counts how many times its operation is requested.
 */
final class CountingTransaction implements FinancialTransaction
{
    public int $operationCalls = 0;

    public function __construct(private readonly FinancialTransaction $transaction) {}

    public function id(): string
    {
        return $this->transaction->id();
    }

    public function number(): int
    {
        return $this->transaction->number();
    }

    public function serial(): string
    {
        return $this->transaction->serial();
    }

    public function type(): DocumentType
    {
        return $this->transaction->type();
    }

    public function customer(): Customer
    {
        return $this->transaction->customer();
    }

    public function issueDate(): DateTimeInterface
    {
        return $this->transaction->issueDate();
    }

    public function operation(): Operation
    {
        ++$this->operationCalls;

        return $this->transaction->operation();
    }
}

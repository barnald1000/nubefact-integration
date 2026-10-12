<?php

declare(strict_types=1);

namespace Savia\NubeFactIntegration\Projection;

use Savia\NubeFactIntegration\Contracts\FinancialTransaction;
use Savia\NubeFactIntegration\Operation;

/**
 * Document projection
 *
 * A document with the projection of its operation. It asks the document for its operation once and projects it once,
 * so the header and the items sent to Nubefact always come from the same operation. It calculates nothing itself and
 * can only be created from the document, so a document can never be paired with another operation's projection.
 */
final readonly class DocumentProjection
{
    private function __construct(
        private FinancialTransaction $transaction,
        private Operation $operation,
        private OperationProjection $projection,
    ) {}

    public static function of(FinancialTransaction $transaction): self
    {
        $operation = $transaction->operation();

        return new self($transaction, $operation, $operation->project());
    }

    public function transaction(): FinancialTransaction
    {
        return $this->transaction;
    }

    public function operation(): Operation
    {
        return $this->operation;
    }

    public function projection(): OperationProjection
    {
        return $this->projection;
    }
}

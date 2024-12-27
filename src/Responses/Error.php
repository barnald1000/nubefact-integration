<?php
/**
 * @author enea dhack <contact@vaened.dev>
 * @link https://vaened.dev DevFolio
 */

declare(strict_types=1);

namespace Savia\NubeFactIntegration\Responses;

use Savia\NubeFactIntegration\Contracts\FinancialTransaction;

final readonly class Error extends Response
{
    public function __construct(
        FinancialTransaction $transaction,
        private string       $message,
        ?array               $response
    )
    {
        parent::__construct($transaction, $response);
    }

    public function isAccept(): bool
    {
        return false;
    }

    public function code(): int
    {
        return (int)$this->get('codigo');
    }

    public function messages(): string|array
    {
        return $this->get('errors');
    }

    public function error(): string
    {
        return $this->message;
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'error'    => $this->error(),
            'messages' => $this->messages(),
        ]);
    }
}
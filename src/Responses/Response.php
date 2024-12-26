<?php
/**
 * @author enea dhack <contact@vaened.dev>
 * @link https://vaened.dev DevFolio
 */

declare(strict_types=1);

namespace Savia\NubeFactIntegration\Responses;

use Savia\NubeFactIntegration\Contracts\FinancialTransaction;

abstract readonly class Response
{
    public function __construct(private FinancialTransaction $transaction, private ?array $response)
    {
    }

    abstract public function isAccept(): bool;

    public function transaction(): FinancialTransaction
    {
        return $this->transaction;
    }

    public function toArray(): array
    {
        return [
            'is_accept' => $this->isAccept(),
        ];
    }

    protected function get(string $key, mixed $default = null): mixed
    {
        return isset($this->response) ? ($this->response[$key] ?? $default) : $default;
    }
}
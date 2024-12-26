<?php
/**
 * @author enea dhack <contact@vaened.dev>
 * @link https://vaened.dev DevFolio
 */

declare(strict_types=1);

namespace Savia\NubeFact;

use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;
use Savia\NubeFact\Contracts\FinancialTransaction;
use Savia\NubeFact\Responses\Error;
use Savia\NubeFact\Responses\Response;
use Savia\NubeFact\Responses\Success;
use Vaened\Support\Types\ArrayList;

use function json_decode;

final readonly class TransactionProcessor
{
    public function __construct(
        private Sender            $sender,
        private ?LoggerRepository $logger = null
    )
    {
    }

    public function process(FinancialTransactions $transactions): ArrayList
    {
        return $transactions->map($this->sendAndLog());
    }

    private function sendAndLog(): callable
    {
        return function (FinancialTransaction $transaction): Response {
            try {
                $response = $this->sender->send($transaction);
                return $this->buildSuccessResponseFor($transaction, $response);
            } catch (RequestException $exception) {
                return $this->buildErrorResponseFor($transaction, $exception, $exception->getResponse());
            }
        };
    }

    private function buildSuccessResponseFor(
        FinancialTransaction $transaction,
        ResponseInterface    $response
    ): Success
    {
        $response = new Success($transaction, json_decode($response->getBody()->getContents(), true));
        $this->logger?->log($response);

        return $response;
    }

    private function buildErrorResponseFor(
        FinancialTransaction $transaction,
        RequestException     $exception,
        ResponseInterface    $response
    ): Error
    {
        $response = new Error($transaction, $exception->getMessage(), json_decode($response->getBody()->getContents(), true));
        $this->logger?->log($response);

        return $response;
    }
}
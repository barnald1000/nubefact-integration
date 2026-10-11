<?php

declare(strict_types=1);

namespace Savia\NubeFactIntegration\Tests;

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use Savia\NubeFactIntegration\Contracts\FinancialTransaction;
use Savia\NubeFactIntegration\FinancialTransactions;
use Savia\NubeFactIntegration\LoggerRepository;
use Savia\NubeFactIntegration\Responses\Error;
use Savia\NubeFactIntegration\Responses\Success;
use Savia\NubeFactIntegration\Sender;
use Savia\NubeFactIntegration\Tests\Support\LinesTransaction;
use Savia\NubeFactIntegration\TransactionProcessor;

use function json_encode;

final class TransactionProcessorTest extends TestCase
{
    #[Test]
    public function it_turns_an_accepted_document_into_a_success_and_logs_it(): void
    {
        $logger      = self::logger();
        $transaction = new LinesTransaction();

        $responses = (new TransactionProcessor(self::senderAnswering(new Response(200, [], (string) json_encode([
            'aceptada_por_sunat' => true,
            'codigo_hash'        => 'xMLFMnbgp1/bHEy572RKRTE9hPY=',
            'enlace_del_pdf'     => 'https://www.nubefact.com/cpe/abc.pdf',
        ]))), $logger))->process(FinancialTransactions::from([$transaction]))->values();

        self::assertCount(1, $responses);
        self::assertInstanceOf(Success::class, $responses[0]);
        self::assertTrue($responses[0]->isAccept());
        self::assertSame('xMLFMnbgp1/bHEy572RKRTE9hPY=', $responses[0]->hashCode());
        self::assertSame($transaction, $responses[0]->transaction());
        self::assertSame([$responses[0]], $logger->logged);
    }

    #[Test]
    public function it_turns_a_rejected_request_into_an_error_and_logs_it(): void
    {
        $logger    = self::logger();
        $exception = new RequestException(
            'Client error: 400 Bad Request',
            new Request('POST', '/'),
            new Response(400, [], (string) json_encode(['errors' => 'El archivo enviado no cumple con el formato establecido', 'codigo' => 20])),
        );

        $responses = (new TransactionProcessor(self::senderThrowing($exception), $logger))
            ->process(FinancialTransactions::from([new LinesTransaction()]))
            ->values();

        self::assertInstanceOf(Error::class, $responses[0]);
        self::assertFalse($responses[0]->isAccept());
        self::assertSame(20, $responses[0]->code());
        self::assertSame('El archivo enviado no cumple con el formato establecido', $responses[0]->messages());
        self::assertSame('Client error: 400 Bad Request', $responses[0]->error());
        self::assertSame([$responses[0]], $logger->logged);
    }

    #[Test]
    public function it_sends_every_transaction_one_by_one_and_works_without_a_logger(): void
    {
        $sender = self::senderAnswering(new Response(200, [], (string) json_encode(['aceptada_por_sunat' => true])));

        $responses = (new TransactionProcessor($sender))
            ->process(FinancialTransactions::from([new LinesTransaction(), new LinesTransaction()]))
            ->values();

        self::assertCount(2, $responses);
        self::assertSame(2, $sender->calls);
    }

    private static function senderAnswering(ResponseInterface $response): Sender
    {
        return new class ($response) implements Sender {
            public int $calls = 0;

            public function __construct(private readonly ResponseInterface $response) {}

            public function send(FinancialTransaction $transaction): ResponseInterface
            {
                ++$this->calls;

                return new Response($this->response->getStatusCode(), [], (string) $this->response->getBody());
            }
        };
    }

    private static function senderThrowing(RequestException $exception): Sender
    {
        return new class ($exception) implements Sender {
            public function __construct(private readonly RequestException $exception) {}

            public function send(FinancialTransaction $transaction): ResponseInterface
            {
                throw $this->exception;
            }
        };
    }

    private static function logger(): LoggerRepository
    {
        return new class () implements LoggerRepository {
            public array $logged = [];

            public function log(Success|Error $response): void
            {
                $this->logged[] = $response;
            }
        };
    }
}

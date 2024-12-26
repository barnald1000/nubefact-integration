<?php
/**
 * @author enea dhack <contact@vaened.dev>
 * @link https://vaened.dev DevFolio
 */

declare(strict_types=1);

namespace Savia\NubeFact\Actions\Payments;

use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Savia\NubeFact\Contracts\FinancialTransaction;
use Savia\NubeFact\Contracts\TransactionEntry;
use Savia\NubeFact\Contracts\TransactionEntryItem;
use Savia\NubeFact\NubeFactService;
use Savia\NubeFact\Operation;
use Savia\NubeFact\Sender;

use function array_merge;
use function sprintf;

final readonly class PaymentsSender implements Sender
{
    public function __construct(private NubeFactService $service)
    {
    }

    public function send(FinancialTransaction $transaction): ResponseInterface
    {
        if (!$transaction instanceof TransactionEntry) {
            throw new InvalidArgumentException(sprintf("Transaction must be an instance of %s", TransactionEntry::class));
        }

        $header = new HeaderSchema($transaction);

        $lines = $transaction->items()
                             ->map(self::toLineScheme())
                             ->map(self::toLineStructure())
                             ->values();

        return $this->service->call(
            Operation::GenerateDocument,
            array_merge($header->value(), [
                'items' => $lines,
            ])
        );
    }

    private static function toLineScheme(): callable
    {
        return static fn(TransactionEntryItem $item) => new LineSchema($item);
    }

    private static function toLineStructure(): callable
    {
        return static fn(LineSchema $line) => $line->value();
    }
}
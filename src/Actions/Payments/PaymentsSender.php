<?php
/**
 * @author enea dhack <contact@vaened.dev>
 * @link https://vaened.dev DevFolio
 */

declare(strict_types=1);

namespace Savia\NubeFactIntegration\Actions\Payments;

use Psr\Http\Message\ResponseInterface;
use Savia\NubeFactIntegration\Contracts\FinancialTransaction;
use Savia\NubeFactIntegration\Keywords\Action;
use Savia\NubeFactIntegration\NubeFactService;
use Savia\NubeFactIntegration\Projection\DocumentProjection;
use Savia\NubeFactIntegration\Projection\LineProjection;
use Savia\NubeFactIntegration\Sender;

use function array_merge;

final readonly class PaymentsSender implements Sender
{
    public function __construct(private NubeFactService $service) {}

    public function send(FinancialTransaction $transaction): ResponseInterface
    {
        $document = DocumentProjection::of($transaction);
        $header   = new HeaderSchema($document);

        $lines = $document->projection()
                          ->lines()
                          ->map(self::toLineScheme())
                          ->map(self::toLineStructure())
                          ->values();

        return $this->service->call(
            Action::GenerateDocument,
            array_merge($header->value(), [
                'items' => $lines,
            ])
        );
    }

    private static function toLineScheme(): callable
    {
        return static fn(LineProjection $line) => new LineSchema($line);
    }

    private static function toLineStructure(): callable
    {
        return static fn(LineSchema $line) => $line->value();
    }
}

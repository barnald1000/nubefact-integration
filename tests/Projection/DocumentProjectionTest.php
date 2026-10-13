<?php

declare(strict_types=1);

namespace Savia\NubeFactIntegration\Tests\Projection;

use PHPUnit\Framework\Attributes\Test;
use Savia\NubeFactIntegration\Lines\Amount;
use Savia\NubeFactIntegration\Lines\IGVTreatment;
use Savia\NubeFactIntegration\Lines\Quantity;
use Savia\NubeFactIntegration\Lines\Unit;
use Savia\NubeFactIntegration\Projection\DocumentProjection;
use Savia\NubeFactIntegration\Tests\Support\BillableItem;
use Savia\NubeFactIntegration\Tests\Support\CountingTransaction;
use Savia\NubeFactIntegration\Tests\Support\ItemsTransaction;
use Savia\NubeFactIntegration\Tests\TestCase;

final class DocumentProjectionTest extends TestCase
{
    #[Test]
    public function it_asks_the_document_for_its_operation_once(): void
    {
        $transaction = new CountingTransaction(new ItemsTransaction(BillableItem::of('CUOTA-03', 'Cuota marzo', Unit::service())->sold(
            quantity : Quantity::of(1),
            unitPrice: Amount::of('500.00'),
            tax      : IGVTreatment::taxedIncluded(),
            discount : Amount::of('250.00'),
        )));

        $document = DocumentProjection::of($transaction);

        self::assertSame(1, $transaction->operationCalls);
        self::assertSame($transaction, $document->transaction());
        self::assertSame('250.00', (string) $document->projection()->total());
        self::assertSame(18.0, (float) (string) $document->operation()->igvRate()->percentage());
    }
}

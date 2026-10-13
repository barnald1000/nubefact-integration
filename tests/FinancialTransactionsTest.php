<?php

declare(strict_types=1);

namespace Savia\NubeFactIntegration\Tests;

use PHPUnit\Framework\Attributes\Test;
use Savia\NubeFactIntegration\FinancialTransactions;
use Savia\NubeFactIntegration\Tests\Support\ItemsTransaction;
use stdClass;
use Vaened\Support\Types\InvalidSafelistItem;

final class FinancialTransactionsTest extends TestCase
{
    #[Test]
    public function it_holds_financial_transactions(): void
    {
        $transaction = new ItemsTransaction();

        self::assertSame([$transaction], FinancialTransactions::from([$transaction])->items());
    }

    #[Test]
    public function it_rejects_anything_that_is_not_a_financial_transaction(): void
    {
        $this->expectException(InvalidSafelistItem::class);

        FinancialTransactions::from([new stdClass()]);
    }
}

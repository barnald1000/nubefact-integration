<?php

declare(strict_types=1);

namespace Savia\NubeFactIntegration\Tests\Keywords;

use DateTimeInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Savia\NubeFactIntegration\Actions\Payments\HeaderSchema;
use Savia\NubeFactIntegration\Contracts\Customer;
use Savia\NubeFactIntegration\Contracts\FinancialTransaction;
use Savia\NubeFactIntegration\Errors\InvalidIgvRate;
use Savia\NubeFactIntegration\Keywords\DocumentType;
use Savia\NubeFactIntegration\Keywords\IgvRate;
use Savia\NubeFactIntegration\Lines\Amount;
use Savia\NubeFactIntegration\Lines\IGVTreatment;
use Savia\NubeFactIntegration\Lines\Quantity;
use Savia\NubeFactIntegration\Lines\Unit;
use Savia\NubeFactIntegration\Operation;
use Savia\NubeFactIntegration\Projection\DocumentProjection;
use Savia\NubeFactIntegration\Projection\LineProjection;
use Savia\NubeFactIntegration\Tests\Support\BillableItem;
use Savia\NubeFactIntegration\Tests\Support\ItemsTransaction;
use Savia\NubeFactIntegration\Tests\TestCase;

final class IgvRateTest extends TestCase
{
    public static function invalidRates(): iterable
    {
        yield 'zero' => [0];
        yield 'zero as float' => [0.0];
        yield 'negative' => [-18];
        yield 'over one hundred' => [100.01];
        yield 'three decimals' => [18.333];
        yield 'not a number' => [NAN];
        yield 'infinite' => [INF];
    }

    #[Test]
    public function the_application_chooses_the_rate_as_a_percentage(): void
    {
        $rate = IgvRate::of(10);

        self::assertSame('0.10', (string) $rate->decimal());
        self::assertSame('1.10', (string) $rate->factor());
        self::assertSame('10.00', (string) $rate->percentage());
        self::assertSame('1.00', (string) IgvRate::of(100)->decimal());
        self::assertSame((string) IgvRate::standard()->decimal(), (string) IgvRate::of(18)->decimal());
    }

    #[Test]
    public function it_accepts_the_rate_as_a_float_read_from_a_decimal_column(): void
    {
        self::assertSame('0.18', (string) IgvRate::of(18.00)->decimal());
        self::assertSame('0.105', (string) IgvRate::of(10.5)->decimal());
        self::assertSame('1.105', (string) IgvRate::of(10.5)->factor());
    }

    #[Test]
    #[DataProvider('invalidRates')]
    public function it_rejects_an_invalid_rate(int|float $percentage): void
    {
        $this->expectException(InvalidIgvRate::class);

        IgvRate::of($percentage);
    }

    #[Test]
    public function every_amount_is_calculated_with_the_chosen_rate(): void
    {
        $rate = IgvRate::of(10);

        $included = LineProjection::of(self::line('110.00', IGVTreatment::taxedIncluded()), $rate);
        $excluded = LineProjection::of(self::line('100.00', IGVTreatment::taxedExcluded()), $rate);

        self::assertSame(['100.00', '10.00', '110.00'], [(string) $included->subtotal(), (string) $included->totalTaxes(), (string) $included->total()]);
        self::assertSame(['100.00', '10.00', '110.00'], [(string) $excluded->subtotal(), (string) $excluded->totalTaxes(), (string) $excluded->total()]);
        self::assertTrue($excluded->unitPrice()->isEqualTo('110'));
    }

    #[Test]
    public function the_header_sends_the_rate_of_the_document(): void
    {
        $transaction = self::transactionAt(IgvRate::of(10), self::line('110.00', IGVTreatment::taxedIncluded()));
        $header      = (new HeaderSchema(DocumentProjection::of($transaction)))->value();

        self::assertSame(10.0, $header['porcentaje_de_igv']);
        self::assertSame(100.0, $header['total_gravada']);
        self::assertSame(10.0, $header['total_igv']);
        self::assertSame(110.0, $header['total']);
    }

    private static function line(string $price, IGVTreatment $treatment): BillableItem
    {
        return BillableItem::of('MAT-001', 'Matrícula 2026', Unit::service())->sold(Quantity::of(1), Amount::of($price), $treatment);
    }

    private static function transactionAt(IgvRate $rate, BillableItem ...$lines): FinancialTransaction
    {
        return new class (new ItemsTransaction(...$lines), $rate) implements FinancialTransaction {
            public function __construct(private readonly FinancialTransaction $base, private readonly IgvRate $rate) {}

            public function id(): string
            {
                return $this->base->id();
            }

            public function number(): int
            {
                return $this->base->number();
            }

            public function serial(): string
            {
                return $this->base->serial();
            }

            public function type(): DocumentType
            {
                return $this->base->type();
            }

            public function customer(): Customer
            {
                return $this->base->customer();
            }

            public function issueDate(): DateTimeInterface
            {
                return $this->base->issueDate();
            }

            public function operation(): Operation
            {
                return Operation::of($this->base->operation()->currency(), $this->rate, $this->base->operation()->items());
            }
        };
    }
}

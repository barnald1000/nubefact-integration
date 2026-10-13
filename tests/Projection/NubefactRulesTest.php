<?php

declare(strict_types=1);

namespace Savia\NubeFactIntegration\Tests\Projection;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use PHPUnit\Framework\Attributes\Test;
use Savia\NubeFactIntegration\Actions\Payments\HeaderSchema;
use Savia\NubeFactIntegration\Actions\Payments\LineSchema;
use Savia\NubeFactIntegration\Keywords\IgvRate;
use Savia\NubeFactIntegration\Keywords\IGVType;
use Savia\NubeFactIntegration\Lines\Amount;
use Savia\NubeFactIntegration\Lines\IGVTreatment;
use Savia\NubeFactIntegration\Lines\Quantity;
use Savia\NubeFactIntegration\Lines\Unit;
use Savia\NubeFactIntegration\Projection\DocumentProjection;
use Savia\NubeFactIntegration\Projection\LineProjection;
use Savia\NubeFactIntegration\Tests\Support\BillableItem;
use Savia\NubeFactIntegration\Tests\Support\ItemsTransaction;
use Savia\NubeFactIntegration\Tests\TestCase;

use function array_chunk;
use function array_map;

/**
 * Nubefact rules
 *
 * Checks the calculation rules of Nubefact's "Manual Cálculo JSON TXT" and of SUNAT's UBL 2.1 guide over a grid of
 * prices, quantities, discounts and IGV treatments, instead of a handful of examples. The header checks run on the
 * final payload, which is where the production error happened: items and header must never disagree.
 */
final class NubefactRulesTest extends TestCase
{
    private const PRICES = ['0.01', '0.05', '0.25', '0.75', '9.99', '10.00', '200.00', '534.10', '1234.57'];

    private const QUANTITIES = [1, 2, 3, 7];

    #[Test]
    public function every_line_follows_the_nubefact_line_formulas(): void
    {
        $rate = IgvRate::standard();

        foreach (self::cases() as $label => [$line, $treatment, $price, $quantity, $discount]) {
            $projection = LineProjection::of($line, $rate);
            $charged    = BigDecimal::of($price)->multipliedBy($quantity)->minus($discount);

            $formulaSubtotal = $projection->unitValue()->multipliedBy($quantity)->minus($projection->totalDiscounts());
            self::assertWithin('0.01', $formulaSubtotal, $projection->subtotal(), "$label: subtotal = valor_unitario × cantidad − descuento");

            self::assertSame(
                (string) $projection->subtotal()->plus($projection->totalTaxes()),
                (string) $projection->total(),
                "$label: total = subtotal + igv",
            );

            if ($treatment->isTaxed()) {
                self::assertWithin('0.01', $projection->subtotal()->multipliedBy($rate->decimal()), $projection->totalTaxes(), "$label: igv = subtotal × tasa");
                self::assertWithin('0.000000001', $projection->unitValue()->multipliedBy($rate->factor()), $projection->unitPrice(), "$label: precio_unitario = valor_unitario × (1 + tasa)");
                self::assertSame(IGVType::GravadoOnerous, $projection->igvType(), "$label: tipo_de_igv");
            } else {
                self::assertSame('0.00', (string) $projection->totalTaxes(), "$label: igv of an untaxed line");
                self::assertTrue($projection->unitPrice()->isEqualTo($projection->unitValue()), "$label: precio_unitario = valor_unitario");
                self::assertSame((string) $charged->toScale(2), (string) $projection->total(), "$label: untaxed total = precio × cantidad − descuento");
            }

            if ($treatment->includesIgv()) {
                self::assertSame((string) $charged->toScale(2), (string) $projection->total(), "$label: with IGV included the total is what was charged");
                self::assertTrue($projection->unitPrice()->isEqualTo($price), "$label: precio_unitario is the price before the discount");
            } elseif ($treatment->isTaxed()) {
                self::assertSame((string) $charged->toScale(2), (string) $projection->subtotal(), "$label: with IGV excluded the subtotal is precio × cantidad − descuento");
            }

            self::assertFalse($projection->subtotal()->isNegative(), "$label: subtotal is never negative");
        }
    }

    #[Test]
    public function the_header_is_always_the_sum_of_the_items_in_the_payload(): void
    {
        $lines = array_map(static fn(array $case): BillableItem => $case[0], self::cases());

        foreach (array_chunk($lines, 5) as $index => $chunk) {
            $transaction = new ItemsTransaction(...$chunk);
            $document    = $transaction->operation()->project();
            $header      = (new HeaderSchema(DocumentProjection::of($transaction)))->value();

            $lineSubtotals = BigDecimal::zero();

            foreach ($document->lines()->items() as $line) {
                $lineSubtotals = $lineSubtotals->plus($line->subtotal());
            }

            self::assertTrue($document->subtotal()->isEqualTo($lineSubtotals), "document $index: subtotal = Σ subtotal de las líneas");
            self::assertTrue($document->subtotal()->isEqualTo($document->totalTaxed()->plus($document->totalExonerated())->plus($document->totalUnaffected())), "document $index: subtotal = gravada + exonerada + inafecta");
            self::assertTrue($document->total()->isEqualTo($document->subtotal()->plus($document->totalTaxes())), "document $index: total = subtotal + igv");
            $items    = array_map(static fn(LineProjection $line): array => (new LineSchema($line))->value(), $document->lines()->items());

            $sum = static function (string $field, ?int $type = null) use ($items): BigDecimal {
                $total = BigDecimal::zero();

                foreach ($items as $item) {
                    if ($type === null || $item['tipo_de_igv'] === $type) {
                        $total = $total->plus(BigDecimal::of((string) $item[$field]));
                    }
                }

                return $total;
            };

            $taxed      = self::payloadAmount($header['total_gravada']);
            $exonerated = self::payloadAmount($header['total_exonerada']);
            $unaffected = self::payloadAmount($header['total_inafecta']);
            $igv        = self::payloadAmount($header['total_igv']);
            $total      = self::payloadAmount($header['total']);

            self::assertTrue($taxed->isEqualTo($sum('subtotal', IGVType::GravadoOnerous->value)), "document $index: total_gravada = Σ subtotal gravado");
            self::assertTrue($exonerated->isEqualTo($sum('subtotal', IGVType::ExoneratedOnerous->value)), "document $index: total_exonerada = Σ subtotal exonerado");
            self::assertTrue($unaffected->isEqualTo($sum('subtotal', IGVType::ExemptOnerous->value)), "document $index: total_inafecta = Σ subtotal inafecto");
            self::assertTrue($igv->isEqualTo($sum('igv')), "document $index: total_igv = Σ igv");
            self::assertTrue(self::payloadAmount($header['total_descuento'])->isEqualTo($sum('descuento')), "document $index: total_descuento = Σ descuento");
            self::assertTrue($total->isEqualTo($sum('total')), "document $index: total = Σ total");
            self::assertTrue($total->isEqualTo($taxed->plus($exonerated)->plus($unaffected)->plus($igv)), "document $index: total = gravada + exonerada + inafecta + igv");
        }
    }

    #[Test]
    public function a_fifty_percent_scholarship_on_a_500_fee_is_sent_as_nubefact_defines_each_field(): void
    {
        $transaction = new ItemsTransaction(BillableItem::of('CUOTA-03', 'Cuota marzo', Unit::service())->sold(
            quantity : Quantity::of(1),
            unitPrice: Amount::of('500.00'),
            tax      : IGVTreatment::taxedIncluded(),
            discount : Amount::of('250.00'),
        ));

        $item   = (new LineSchema($transaction->operation()->project()->lines()->items()[0]))->value();
        $header = (new HeaderSchema(DocumentProjection::of($transaction)))->value();

        self::assertSame(423.7288135593, $item['valor_unitario'], 'valor_unitario: sin IGV = 500 / 1.18');
        self::assertSame(500.0, $item['precio_unitario'], 'precio_unitario: con IGV, antes del descuento');
        self::assertSame(211.86, $item['descuento'], 'descuento: antes de impuestos = 250 / 1.18');
        self::assertSame(211.86, $item['subtotal'], 'subtotal: valor de venta de la línea');
        self::assertSame(38.14, $item['igv'], 'igv: IGV de la línea');
        self::assertSame(250.0, $item['total'], 'total: lo que paga el alumno');
        self::assertSame(1, $item['tipo_de_igv'], 'tipo_de_igv: gravado, operación onerosa');

        self::assertSame(211.86, $header['total_gravada'], 'total_gravada = Σ subtotal gravado');
        self::assertSame(38.14, $header['total_igv'], 'total_igv = Σ igv de líneas');
        self::assertSame(211.86, $header['total_descuento'], 'total_descuento = Σ descuento de ítems');
        self::assertSame(250.0, $header['total'], 'total = gravada + exonerada + inafecta + igv');
    }

    #[Test]
    public function it_rounds_half_up(): void
    {
        self::assertSame('0.05', (string) self::excluded('0.25')->totalTaxes(), '0.25 × 0.18 = 0.045');
        self::assertSame('0.14', (string) self::excluded('0.75')->totalTaxes(), '0.75 × 0.18 = 0.135');
    }

    #[Test]
    public function the_discount_belongs_to_the_whole_line_not_to_each_unit(): void
    {
        $projection = LineProjection::of(self::item()->sold(
            quantity : Quantity::of(3),
            unitPrice: Amount::of('15.00'),
            tax      : IGVTreatment::taxedIncluded(),
            discount : Amount::of('5.00'),
        ), IgvRate::standard());

        self::assertSame('40.00', (string) $projection->total());
        self::assertSame('4.24', (string) $projection->totalDiscounts());
    }

    /**
     * @return array<string, array{BillableItem, IGVTreatment, string, int, BigDecimal}>
     */
    private static function cases(): array
    {
        $treatments = [
            'taxed included' => IGVTreatment::taxedIncluded(),
            'taxed excluded' => IGVTreatment::taxedExcluded(),
            'exonerated'     => IGVTreatment::exonerated(),
            'unaffected'     => IGVTreatment::unaffected(),
        ];

        $cases = [];

        foreach ($treatments as $name => $treatment) {
            foreach (self::PRICES as $price) {
                foreach (self::QUANTITIES as $quantity) {
                    $gross = BigDecimal::of($price)->multipliedBy($quantity);

                    foreach (['none' => null, 'third' => $gross->dividedBy(3, 2, RoundingMode::Down), 'full' => $gross] as $kind => $discount) {
                        $cases["$name, $price × $quantity, discount $kind"] = [
                            self::item()->sold(
                                quantity : Quantity::of($quantity),
                                unitPrice: Amount::of($price),
                                tax      : $treatment,
                                discount : $discount === null ? null : Amount::of((string) $discount),
                            ),
                            $treatment,
                            $price,
                            $quantity,
                            $discount ?? BigDecimal::zero(),
                        ];
                    }
                }
            }
        }

        return $cases;
    }

    private static function excluded(string $price): LineProjection
    {
        return LineProjection::of(self::item()->sold(
            quantity : Quantity::of(1),
            unitPrice: Amount::of($price),
            tax      : IGVTreatment::taxedExcluded(),
        ), IgvRate::standard());
    }

    private static function payloadAmount(float $value): BigDecimal
    {
        return BigDecimal::of((string) $value);
    }

    private static function assertWithin(string $tolerance, BigDecimal $expected, BigDecimal $actual, string $message): void
    {
        self::assertTrue(
            $expected->minus($actual)->abs()->isLessThanOrEqualTo($tolerance),
            "$message (expected $expected, got $actual, tolerance $tolerance)",
        );
    }

    private static function item(): BillableItem
    {
        return BillableItem::of('MAT-001', 'Matrícula 2026', Unit::service());
    }
}

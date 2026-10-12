<?php

declare(strict_types=1);

namespace Savia\NubeFactIntegration\Tests\Keywords;

use PHPUnit\Framework\Attributes\Test;
use Savia\NubeFactIntegration\Keywords\Action;
use Savia\NubeFactIntegration\Keywords\Currency;
use Savia\NubeFactIntegration\Keywords\DocumentType;
use Savia\NubeFactIntegration\Keywords\IdentificationType;
use Savia\NubeFactIntegration\Keywords\IgvRate;
use Savia\NubeFactIntegration\Keywords\IGVType;
use Savia\NubeFactIntegration\Tests\TestCase;

/**
 * Keywords
 *
 * Pins every code to the value of Nubefact's JSON manual. A wrong code is not a calculation error, but Nubefact would
 * emit a different document (or reject it), so these values must never change by accident.
 */
final class KeywordsTest extends TestCase
{
    #[Test]
    public function currencies_use_the_nubefact_codes(): void
    {
        self::assertSame(1, Currency::PEN->value);
        self::assertSame(2, Currency::USD->value);
        self::assertSame(3, Currency::EUR->value);
    }

    #[Test]
    public function document_types_use_the_nubefact_codes(): void
    {
        self::assertSame(1, DocumentType::Invoice->value);
        self::assertSame(2, DocumentType::Receipt->value);
        self::assertSame(3, DocumentType::CreditNote->value);
        self::assertSame(4, DocumentType::DebitNote->value);
    }

    #[Test]
    public function identification_types_use_the_nubefact_codes(): void
    {
        self::assertSame(1, IdentificationType::Dni->value);
        self::assertSame(4, IdentificationType::CE->value);
        self::assertSame(6, IdentificationType::Ruc->value);
        self::assertSame(7, IdentificationType::Passport->value);
    }

    #[Test]
    public function igv_types_use_the_nubefact_codes(): void
    {
        $expected = [
            'GravadoOnerous'            => 1,
            'GravadoPrize'              => 2,
            'GravadoDonation'           => 3,
            'GravadoWithdrawal'         => 4,
            'GravadoAdvertising'        => 5,
            'GravadoBonuses'            => 6,
            'GravadoEmployeeGifts'      => 7,
            'ExoneratedOnerous'         => 8,
            'ExemptOnerous'             => 9,
            'ExemptBonus'               => 10,
            'ExemptWithdrawal'          => 11,
            'ExemptMedicalSamples'      => 12,
            'ExemptCollectiveAgreement' => 13,
            'ExemptPrize'               => 14,
            'ExemptAdvertising'         => 15,
            'Exportation'               => 16,
            'ExoneratedFreeTransfer'    => 17,
        ];

        foreach (IGVType::cases() as $case) {
            self::assertSame($expected[$case->name], $case->value, $case->name);
        }

        self::assertCount(17, IGVType::cases());
    }

    #[Test]
    public function the_action_generates_a_document(): void
    {
        self::assertSame('generar_comprobante', Action::GenerateDocument->value);
    }

    #[Test]
    public function the_standard_igv_rate_is_eighteen_percent(): void
    {
        $rate = IgvRate::standard();

        self::assertSame('0.18', (string) $rate->decimal());
        self::assertSame('1.18', (string) $rate->factor());
        self::assertSame('18.00', (string) $rate->percentage());
    }
}

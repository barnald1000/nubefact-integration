<?php

declare(strict_types=1);

namespace Savia\NubeFactIntegration\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Savia\NubeFactIntegration\Customer;
use Savia\NubeFactIntegration\Errors\InvalidCustomer;
use Savia\NubeFactIntegration\Keywords\IdentificationType;

use function str_repeat;

final class CustomerTest extends TestCase
{
    public static function invalidCustomers(): iterable
    {
        yield 'empty document number' => ['', 'Juan Pérez', null, null];
        yield 'blank document number' => ['   ', 'Juan Pérez', null, null];
        yield 'document number over 15' => [str_repeat('1', 16), 'Juan Pérez', null, null];
        yield 'empty denomination' => ['12345678', '', null, null];
        yield 'denomination over 100' => ['12345678', str_repeat('a', 101), null, null];
        yield 'empty address' => ['12345678', 'Juan Pérez', '', null];
        yield 'address over 100' => ['12345678', 'Juan Pérez', str_repeat('a', 101), null];
        yield 'invalid email' => ['12345678', 'Juan Pérez', null, 'not-an-email'];
        yield 'empty email' => ['12345678', 'Juan Pérez', null, ''];
        yield 'email over 250' => ['12345678', 'Juan Pérez', null, str_repeat('a', 245) . '@x.com'];
    }

    #[Test]
    public function it_keeps_a_valid_customer(): void
    {
        $customer = Customer::of(
            identificationType  : IdentificationType::Ruc,
            identificationNumber: '20600695771',
            denomination        : 'NUBEFACT SA',
            address             : 'CALLE LIBERTAD 116 MIRAFLORES - LIMA - PERU',
            email               : 'cliente@example.com',
        );

        self::assertSame(IdentificationType::Ruc, $customer->identificationType());
        self::assertSame('20600695771', $customer->identificationNumber());
        self::assertSame('NUBEFACT SA', $customer->denomination());
        self::assertSame('CALLE LIBERTAD 116 MIRAFLORES - LIMA - PERU', $customer->address());
        self::assertSame('cliente@example.com', $customer->email());
    }

    #[Test]
    public function it_allows_a_customer_without_address_or_email(): void
    {
        $customer = Customer::of(IdentificationType::Dni, '12345678', 'Juan Pérez');

        self::assertNull($customer->address());
        self::assertNull($customer->email());
    }

    #[Test]
    public function it_counts_characters_not_bytes(): void
    {
        $denomination = str_repeat('ñ', 100);

        self::assertSame($denomination, Customer::of(IdentificationType::Dni, '12345678', $denomination)->denomination());
    }

    #[Test]
    #[DataProvider('invalidCustomers')]
    public function it_rejects_invalid_customer_data(string $number, string $denomination, ?string $address, ?string $email): void
    {
        $this->expectException(InvalidCustomer::class);

        Customer::of(IdentificationType::Dni, $number, $denomination, $address, $email);
    }
}

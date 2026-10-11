<?php

declare(strict_types=1);

namespace Savia\NubeFactIntegration;

use Savia\NubeFactIntegration\Errors\InvalidCustomer;
use Savia\NubeFactIntegration\Keywords\IdentificationType;

use function filter_var;
use function mb_strlen;
use function trim;

use const FILTER_VALIDATE_EMAIL;

/**
 * The buyer of a document: identification, name and optional contact data. Lengths follow Nubefact's limits.
 */
final readonly class Customer
{
    private function __construct(
        private IdentificationType $identificationType,
        private string $identificationNumber,
        private string $denomination,
        private ?string $address,
        private ?string $email,
    ) {}

    public static function of(
        IdentificationType $identificationType,
        string $identificationNumber,
        string $denomination,
        ?string $address = null,
        ?string $email = null,
    ): self {
        self::assertText($identificationNumber, 15, 'El número de documento del cliente');
        self::assertText($denomination, 100, 'La denominación del cliente');

        if ($address !== null) {
            self::assertText($address, 100, 'La dirección del cliente');
        }

        if ($email !== null && (mb_strlen($email) > 250 || filter_var($email, FILTER_VALIDATE_EMAIL) === false)) {
            throw new InvalidCustomer('El email del cliente no es válido.');
        }

        return new self($identificationType, $identificationNumber, $denomination, $address, $email);
    }

    public function identificationType(): IdentificationType
    {
        return $this->identificationType;
    }

    public function identificationNumber(): string
    {
        return $this->identificationNumber;
    }

    public function denomination(): string
    {
        return $this->denomination;
    }

    public function address(): ?string
    {
        return $this->address;
    }

    public function email(): ?string
    {
        return $this->email;
    }

    private static function assertText(string $value, int $maxLength, string $field): void
    {
        if (trim($value) === '' || mb_strlen($value) > $maxLength) {
            throw new InvalidCustomer("{$field} debe tener entre 1 y {$maxLength} caracteres.");
        }
    }
}

<?php
/**
 * @author enea dhack <contact@vaened.dev>
 * @link https://vaened.dev DevFolio
 */

declare(strict_types=1);

namespace Savia\NubeFactIntegration\Tests\Support;

use Savia\NubeFactIntegration\Contracts\Customer;
use Savia\NubeFactIntegration\Keywords\IdentificationType;

final readonly class GenericClient implements Customer
{
    public function __construct(
        private string $denomination,
        private ?string $email,
        private ?string $address,
        private string $identificationNumber,
        private IdentificationType $identificationType
    ) {}

    public function denomination(): string
    {
        return $this->denomination;
    }

    public function email(): ?string
    {
        return $this->email;
    }

    public function address(): ?string
    {
        return $this->address;
    }

    public function identificationNumber(): string
    {
        return $this->identificationNumber;
    }

    public function identificationType(): IdentificationType
    {
        return $this->identificationType;
    }
}

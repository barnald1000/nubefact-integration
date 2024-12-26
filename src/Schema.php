<?php
/**
 * @author enea dhack <contact@vaened.dev>
 * @link https://vaened.dev DevFolio
 */

declare(strict_types=1);

namespace Savia\NubeFact;

use Savia\NubeFact\Errors\InvalidPaymentStructure;

use function Lambdish\Phunctional\each;

abstract class Schema
{
    abstract protected static function required(): array;

    abstract protected function structure(): array;

    public function value(): array
    {
        $structure = $this->structure();
        $this->validate($structure);

        return $structure;
    }

    protected function validate(array $structure): void
    {
        each(
            static fn($required) => key_exists($required, $structure) ||
                throw new InvalidPaymentStructure("The required key `$required` is missing from the payment structure."),
            static::required()
        );
    }
}
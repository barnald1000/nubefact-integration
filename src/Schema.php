<?php
/**
 * @author enea dhack <contact@vaened.dev>
 * @link https://vaened.dev DevFolio
 */

declare(strict_types=1);

namespace Savia\NubeFact;

use Savia\NubeFact\Errors\InvalidPaymentStructure;

use function Lambdish\Phunctional\each;
use function Lambdish\Phunctional\map;

abstract class Schema
{
    private const PRECISION = 2;

    abstract protected static function required(): array;

    abstract protected static function valuesToRound(): array;

    abstract protected function structure(): array;

    public function value(): array
    {
        $structure = $this->structure();
        $this->validate($structure);

        return map(self::roundIfNeeded(), $structure);
    }

    protected function validate(array $structure): void
    {
        each(
            static fn($required) => key_exists($required, $structure) ||
                throw new InvalidPaymentStructure("The required key `$required` is missing from the payment structure."),
            static::required()
        );
    }

    private static function roundIfNeeded(): callable
    {
        return static function (mixed $value, string $key): mixed {
            if (in_array($key, static::valuesToRound())) {
                return round($value, self::PRECISION);
            }

            return $value;
        };
    }
}
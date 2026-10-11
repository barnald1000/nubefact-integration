<?php

declare(strict_types=1);

namespace Savia\NubeFactIntegration;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;

use function rtrim;
use function str_contains;

/**
 * The single place that decides how a decimal is written as text in a payload.
 */
final class DecimalFormat
{
    private function __construct() {}

    /**
     * Rounds to the given scale and drops trailing zeros: 245.00 becomes "245" and 4.50 becomes "4.5".
     */
    public static function trimmed(BigDecimal $value, int $scale): string
    {
        $text = (string) $value->toScale($scale, RoundingMode::HalfUp);

        return str_contains($text, '.') ? rtrim(rtrim($text, '0'), '.') : $text;
    }
}

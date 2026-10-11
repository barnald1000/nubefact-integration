<?php

declare(strict_types=1);

namespace Savia\NubeFactIntegration;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;

/**
 * The single place that decides how a decimal is written in a payload: as a number rounded to the field's scale.
 */
final class DecimalFormat
{
    private function __construct() {}

    public static function number(BigDecimal $value, int $scale): float
    {
        return $value->toScale($scale, RoundingMode::HalfUp)->toFloat();
    }
}

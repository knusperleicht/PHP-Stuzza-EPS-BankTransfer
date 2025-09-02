<?php
declare(strict_types=1);

namespace Knusperleicht\EpsBankTransfer\Utilities;

use InvalidArgumentException;

final class MoneyFormatter
{
    /**
     * Convert an integer euro-cent amount to EPS XSD decimal string (e.g., 1234 -> "12.34").
     *
     * @param int|string $val Integer cents (or numeric string of digits)
     * @return string Decimal amount with two fraction digits and dot as separator
     * @throws InvalidArgumentException When the value is not an int or digit string
     */
    public static function formatXsdDecimal($val): string
    {
        if (is_string($val) && ctype_digit($val)) {
            $val = (int)$val;
        }

        if (!is_int($val)) {
            throw new InvalidArgumentException(
                sprintf("Int value (cents) expected but %s received", gettype($val))
            );
        }

        $formatted = number_format($val / 100, 2, '.', '');
        return sprintf("%.2f", (float)$formatted);
    }
}
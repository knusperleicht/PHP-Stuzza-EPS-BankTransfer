<?php
declare(strict_types=1);

namespace Externet\EpsBankTransfer\Utilities;

final class MoneyFormatter
{
    public static function formatXsdDecimal($val): string
    {
        if (is_string($val) && preg_match('/^[0-9]+$/', $val) > 0) {
            $val += 0;
        }

        if (!is_int($val)) {
            throw new \InvalidArgumentException(sprintf("Int value expected but %s received", gettype($val)));
        }

        if (strlen((string)$val) < 3) {
            if (strlen((string)$val) < 1) {
                return '0.00';
            }

            $prefix = strlen((string)$val) < 2 ? '0.0' : '0.';
            return $prefix . $val;
        }

        $intVal = substr((string)$val, 0, -2);
        $centVal = substr((string)$val, -2);
        return $intVal . '.' . $centVal;
    }
}
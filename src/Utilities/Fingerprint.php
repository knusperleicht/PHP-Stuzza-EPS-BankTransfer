<?php
declare(strict_types=1);

namespace Externet\EpsBankTransfer\Utilities;

class Fingerprint
{
    public static function calculateExpectedMD5Fingerprint(string $secret, string $date, string $reference, string $account, string $remittance, int $amount, string $currency, string $userId): string
    {
        return md5($secret . $date . $reference . $account . $remittance . MoneyFormatter::formatXsdDecimal($amount) . $currency . $userId);
    }
}

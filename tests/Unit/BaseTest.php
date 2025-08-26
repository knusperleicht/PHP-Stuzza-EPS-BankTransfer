<?php
declare(strict_types=1);

namespace Externet\EpsBankTransfer\Tests;

use PHPUnit\Framework\TestCase;

abstract class BaseTest extends TestCase
{
    public static function getEpsData($filename)
    {
        return file_get_contents(self::getEpsDataPath($filename));
    }

    public static function getEpsDataPath($filename): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR . 'EpsData' . DIRECTORY_SEPARATOR . $filename;
    }
}

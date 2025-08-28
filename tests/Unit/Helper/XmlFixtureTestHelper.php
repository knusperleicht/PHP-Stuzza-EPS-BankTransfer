<?php

declare(strict_types=1);

namespace Externet\EpsBankTransfer\Tests\Helper;

trait XmlFixtureTestHelper
{
    protected function assertXmlEqualsFixture(string $expectedFile, string $actualXml): void
    {
        $expected = new \DOMDocument();
        $expected->loadXML($this->loadFixture($expectedFile));
        $expected->formatOutput = true;
        $expected->preserveWhiteSpace = false;
        $expected->normalizeDocument();

        $actual = new \DOMDocument();
        $actual->loadXML($actualXml);
        $actual->formatOutput = true;
        $actual->preserveWhiteSpace = false;
        $actual->normalizeDocument();

        $this->assertEquals($expected->saveXML(), $actual->saveXML());
    }

    
    
    protected static function loadFixture(string $filename): string
    {
        return file_get_contents(self::fixturePath($filename));
    }

    protected static function fixturePath(string $filename): string
    {
        return dirname(__DIR__, 2) . '/Fixtures/' . $filename;
    }
}
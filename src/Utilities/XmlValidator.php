<?php
declare(strict_types=1);

namespace Externet\EpsBankTransfer\Utilities;

use Externet\EpsBankTransfer\Exceptions\XmlValidationException;

class XmlValidator
{
    /**
     * @throws XmlValidationException
     */
    public static function ValidateBankList($xml): bool
    {
        return self::ValidateXml($xml, self::GetXSD('epsSOBankListProtocol.xsd'));
    }

    /**
     * @throws XmlValidationException
     */
    public static function ValidateEpsProtocol($xml, string $version = null): bool
    {
        $filename = $version ? "EPSProtocol-V{$version}.xsd" : 'EPSProtocol-V26.xsd';
        return self::ValidateXml($xml, self::GetXSD($filename));
    }

    /**
     * @throws XmlValidationException
     */
    public static function ValidateEpsRefund($xml, string $version = null): bool
    {
        $filename = $version ? "EPSRefund-V{$version}.xsd" : 'EPSRefund-V26.xsd';
        return self::ValidateXml($xml, self::GetXSD($filename));
    }

    // HELPER FUNCTIONS

    private static function GetXSD($filename): string
    {
        return dirname(__DIR__, 2)
            . DIRECTORY_SEPARATOR . 'resources'
            . DIRECTORY_SEPARATOR . 'schemas'
            . DIRECTORY_SEPARATOR . $filename;
    }

    /**
     * @throws XmlValidationException
     */
    private static function ValidateXml($xml, $xsd): bool
    {
        if (empty($xml)) {
            throw new XmlValidationException('XML is empty');
        }
        $doc = new \DOMDocument();
        try {
            $doc->loadXML($xml);
        } catch (\Exception $e) {
            throw new XmlValidationException('Failed to load XML: ' . $e->getMessage());
        }
        $prevState = libxml_use_internal_errors(true);
        if (!$doc->schemaValidate($xsd)) {
            $xmlError = libxml_get_last_error();
            libxml_use_internal_errors($prevState);

            throw new XmlValidationException('XML does not validate against XSD. ' . $xmlError->message);
        }
        libxml_use_internal_errors($prevState);
        return true;
    }
}
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
    public static function ValidateEpsProtocol($xml): bool
    {
        return self::ValidateXml($xml, self::GetXSD('EPSProtocol-V26.xsd'));
    }

    /**
     * @throws XmlValidationException
     */
    public static function ValidateEpsRefund($xml): bool
    {
        return self::ValidateXml($xml, self::GetXSD('EPSRefund-V26.xsd'));
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
        if (empty($xml))
        {
            throw new XmlValidationException('XML is empty');
        }
        $doc = new \DOMDocument();
        $doc->loadXml($xml);
        $prevState = libxml_use_internal_errors(true);
        if (!$doc->schemaValidate($xsd))
        {
            $xmlError = libxml_get_last_error();
            libxml_use_internal_errors($prevState);
            
            throw new XmlValidationException('XML does not validate against XSD. ' . $xmlError->message);
        }
        return true;
    }
}

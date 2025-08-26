<?php
declare(strict_types=1);

namespace Externet\EpsBankTransfer;

use Exception;

class EpsRefundResponse
{
    /** @var string Response status code (max length 3) */
    public $StatusCode;

    /** @var string|null Error message (optional, max length 255) */
    public $ErrorMsg;

    /**
     * Convert the object to a SimpleXmlElement structure.
     *
     * @return EpsXmlElement
     * @throws Exception
     */
    public function GetSimpleXml(): EpsXmlElement
    {
        $xml = EpsXmlElement::createEmptySimpleXml('epsr:EpsRefundResponse xmlns:epsr="http://www.stuzza.at/namespaces/eps/refund/2018/09"');

        $xml->addChildExt('StatusCode', $this->StatusCode, 'epsr');

        if (!empty($this->ErrorMsg)) {
            $xml->addChildExt('ErrorMsg', $this->ErrorMsg, 'epsr');
        }

        return $xml;
    }
}
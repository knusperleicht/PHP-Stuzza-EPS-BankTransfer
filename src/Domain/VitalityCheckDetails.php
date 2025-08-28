<?php
declare(strict_types=1);

namespace Externet\EpsBankTransfer\Domain;

use Externet\EpsBankTransfer\Utilities\Constants;
use SimpleXMLElement;

class VitalityCheckDetails
{

    /** @var SimpleXMLElement */
    public $simpleXml;

    private $remittanceIdentifier;

    public function __construct($simpleXml)
    {
        $this->simpleXml = $simpleXml;
        $this->init($this->simpleXml);
    }

    /**
     *
     * @param SimpleXMLElement $simpleXml
     */
    private function init(SimpleXMLElement $simpleXml)
    {
        $epspChildren = $simpleXml->children(Constants::XMLNS_epsp);
        $VitalityCheckDetails = $epspChildren[0];
        $t2 = $VitalityCheckDetails->children(Constants::XMLNS_epi);
        $this->remittanceIdentifier = null;

        if (isset($t2->RemittanceIdentifier))
        {
            $this->setRemittanceIdentifier($t2->RemittanceIdentifier);
        }
        elseif (isset($t2->UnstructuredRemittanceIdentifier))
        {
            $this->setRemittanceIdentifier($t2->UnstructuredRemittanceIdentifier);
        }
        if ($this->remittanceIdentifier == null)
        {
            throw new \LogicException('Could not find RemittanceIdentifier in XML');
        }
    }

    public function setRemittanceIdentifier($a)
    {
        $this->remittanceIdentifier = (string) $a;
    }
    
    /**
     * Gets epi:RemittanceIdentifier or epi:UnstructuredRemittanceIdentifier - depending on which one is present in the XML file
     */
    public function getRemittanceIdentifier()
    {
        return $this->remittanceIdentifier;
    }
}

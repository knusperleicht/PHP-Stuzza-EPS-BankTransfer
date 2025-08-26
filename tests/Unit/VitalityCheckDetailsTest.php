<?php
declare(strict_types=1);

namespace Externet\EpsBankTransfer\Tests;

use Exception;
use Externet\EpsBankTransfer\VitalityCheckDetails;
use SimpleXMLElement;

class VitalityCheckDetailsTest extends BaseTest
{
    /** @var SimpleXMLElement */
    private $simpleXml;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->simpleXml = new SimpleXMLElement($this->getEpsData('VitalityCheckDetails.xml'));
    }

    public function testGetRemittanceIdentifier()
    {
        $vitalityCheckDetails = new VitalityCheckDetails($this->simpleXml);
        $actual = $vitalityCheckDetails->GetRemittanceIdentifier();

        $this->assertEquals('AT1234567890XYZ', $actual);
    }
}
<?php
declare(strict_types=1);

namespace Externet\EpsBankTransfer\Tests\Domain;

use Exception;
use Externet\EpsBankTransfer\Domain\VitalityCheckDetails;
use Externet\EpsBankTransfer\Tests\Helper\XmlFixtureTestHelper;
use PHPUnit\Framework\TestCase;
use SimpleXMLElement;

class VitalityCheckDetailsTest extends TestCase
{
    use XmlFixtureTestHelper;

    /** @var SimpleXMLElement */
    private $simpleXml;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->simpleXml = new SimpleXMLElement($this->loadFixture('VitalityCheckDetails.xml'));
    }

    public function testGetRemittanceIdentifier()
    {
        $vitalityCheckDetails = new VitalityCheckDetails($this->simpleXml);
        $actual = $vitalityCheckDetails->getRemittanceIdentifier();

        $this->assertEquals('AT1234567890XYZ', $actual);
    }
}
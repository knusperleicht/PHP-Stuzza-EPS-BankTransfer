<?php
declare(strict_types=1);

namespace Externet\EpsBankTransfer\Tests\Utilities;

use Externet\EpsBankTransfer\Exceptions\XmlValidationException;
use Externet\EpsBankTransfer\Tests\Helper\XmlFixtureTestTrait;
use Externet\EpsBankTransfer\Utilities\XmlValidator;
use PHPUnit\Framework\TestCase;

class XmlValidatorTest extends TestCase
{
    use XmlFixtureTestTrait;

    public function testBanksThrowsExceptionOnEmptyData(): void
    {
        $this->expectException(XmlValidationException::class);
        XmlValidator::ValidateBankList('');
    }

    public function testBanksThrowsExceptionOnInvalidData(): void
    {
        $this->expectException(XmlValidationException::class);
        XmlValidator::ValidateBankList('bar');
    }

    public function testBanksThrowsExceptionOnInvalidXml(): void
    {
        $this->expectException(XmlValidationException::class);
        XmlValidator::ValidateBankList($this->loadFixture('BankListInvalid.xml'));
    }

    /**
     * @throws XmlValidationException
     */
    public function testBanksReturnsXmlString(): void
    {
        $result = XmlValidator::ValidateBankList($this->loadFixture('BankListSample.xml'));
        $this->assertTrue($result);
    }

    /**
     * @throws XmlValidationException
     */
    public function testWithSignatureReturnsTrue(): void
    {
        $result = XmlValidator::ValidateEpsProtocol($this->loadFixture('BankConfirmationDetailsWithSignature.xml'));
        $this->assertTrue($result);
    }

    /**
     * @throws XmlValidationException
     */
    public function testRefundResponseValid(): void
    {
        $result = XmlValidator::ValidateEpsRefund($this->loadFixture('RefundResponseAccepted000.xml'));
        $this->assertTrue($result);
    }
}
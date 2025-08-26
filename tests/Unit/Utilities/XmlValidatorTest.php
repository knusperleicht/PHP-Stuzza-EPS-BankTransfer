<?php
declare(strict_types=1);

namespace Externet\EpsBankTransfer\Tests\Utilities;

use Externet\EpsBankTransfer\Exceptions\XmlValidationException;
use Externet\EpsBankTransfer\Tests\BaseTest;
use Externet\EpsBankTransfer\Utilities\XmlValidator;

class XmlValidatorTest extends BaseTest
{
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
        XmlValidator::ValidateBankList($this->getEpsData('BankListInvalid.xml'));
    }

    /**
     * @throws XmlValidationException
     */
    public function testBanksReturnsXmlString(): void
    {
        $result = XmlValidator::ValidateBankList($this->getEpsData('BankListSample.xml'));
        $this->assertTrue($result);
    }

    /**
     * @throws XmlValidationException
     */
    public function testWithSignatureReturnsTrue(): void
    {
        $result = XmlValidator::ValidateEpsProtocol($this->getEpsData('BankConfirmationDetailsWithSignature.xml'));
        $this->assertTrue($result);
    }

    /**
     * @throws XmlValidationException
     */
    public function testRefundResponseValid(): void
    {
        $result = XmlValidator::ValidateEpsRefund($this->getEpsData('RefundResponseAccepted000.xml'));
        $this->assertTrue($result);
    }
}
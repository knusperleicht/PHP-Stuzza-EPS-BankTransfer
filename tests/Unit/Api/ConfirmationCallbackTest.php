<?php
declare(strict_types=1);

namespace Externet\EpsBankTransfer\Tests\Api;

use Externet\EpsBankTransfer\Exceptions\CallbackResponseException;
use Externet\EpsBankTransfer\Exceptions\InvalidCallbackException;
use Externet\EpsBankTransfer\Exceptions\ShopResponseException;
use Externet\EpsBankTransfer\Exceptions\XmlValidationException;
use Externet\EpsBankTransfer\Generated\Protocol\V26\BankConfirmationDetails;
use Externet\EpsBankTransfer\Tests\Helper\SoV26CommunicatorTestTrait;
use Externet\EpsBankTransfer\Utilities\XmlValidator;
use PHPUnit\Framework\TestCase;

class ConfirmationCallbackTest extends TestCase
{
    use SoV26CommunicatorTestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpCommunicator();
    }

    private function handleConfirmation(
        ?callable $bankCallback,
                  $vitalityCallback,
        string $xmlFile,
        ?string $outputFile = null
    ): void {
        $dataPath = $this->fixturePath($xmlFile);
        $this->target->handleConfirmationUrl(
            $bankCallback,
            $vitalityCallback,
            $dataPath,
            $outputFile ?? 'php://temp'
        );
    }

    public function testHandleConfirmationUrlThrowsExceptionOnMissingCallback(): void
    {
        $this->expectException(InvalidCallbackException::class);
        $this->expectExceptionMessage('confirmationCallback not callable or missing');
        $this->handleConfirmation(null, null, 'BankConfirmationDetailsWithoutSignature.xml');
    }

    public function testHandleConfirmationUrlReturnsErrorOnMissingCallback(): void
    {
        $temp = tempnam(sys_get_temp_dir(), 'SoCommunicatorTest_');
        try {
            $this->handleConfirmation(null, null, 'BankConfirmationDetailsWithoutSignature.xml', $temp);
        } catch (InvalidCallbackException $e) {
            $msg = $e->getMessage();
        }
        $actual = file_get_contents($temp);
        XmlValidator::ValidateEpsProtocol($actual);
        $this->assertStringContainsString($msg, $actual);
    }

    public function testHandleConfirmationUrlThrowsExceptionOnInvalidXml(): void
    {
        $this->expectException(XmlValidationException::class);
        $this->handleConfirmation(
            function () { return true; },
            null,
            'BankConfirmationDetailsInvalid.xml'
        );
    }

    public function testHandleConfirmationUrlCallsCallback(): void
    {
        $actual = 'Nothing';
        $this->handleConfirmation(
            function ($data) use (&$actual) {
                $actual = $data;
                return true;
            },
            null,
            'BankConfirmationDetailsWithoutSignature.xml'
        );

        $expected = file_get_contents($this->fixturePath('BankConfirmationDetailsWithoutSignature.xml'));
        $this->assertSame($expected, $actual);
    }

    /**
     * @throws XmlValidationException
     */
    public function testHandleConfirmationUrlCallsCallbackWithBankConfirmationDetails(): void
    {
        $bankDetails = null;
        $temp = tempnam(sys_get_temp_dir(), 'SoCommunicatorTest_');
        $this->handleConfirmation(
            function ($raw, $bc) use (&$bankDetails) {
                $bankDetails = $bc;
                return true;
            },
            null,
            'BankConfirmationDetailsWithoutSignature.xml',
            $temp
        );

        $this->assertInstanceOf(BankConfirmationDetails::class, $bankDetails);
        $this->assertEquals('AT1234567890XYZ', $bankDetails->getPaymentConfirmationDetails()->getRemittanceIdentifier());
        $this->assertEquals('13212452dea', $bankDetails->getSessionId());
        $this->assertEquals('AAAAAAAAAAA', $bankDetails->getPaymentConfirmationDetails()->getPayConApprovingUnitDetails()->getApprovingUnitBankIdentifier());
        $this->assertEquals(
            '2007-03-19T11:11:00.000000',
            $bankDetails->getPaymentConfirmationDetails()->getPayConApprovalTime()->format('Y-m-d\TH:i:s.u')
        );
        $this->assertEquals('120000302122320812201106461', $bankDetails->getPaymentConfirmationDetails()->getPaymentReferenceIdentifier());
        $this->assertEquals('OK', $bankDetails->getPaymentConfirmationDetails()->getStatusCode());

        $actual = file_get_contents($temp);
        XmlValidator::ValidateEpsProtocol($actual);
        $this->assertStringContainsString($bankDetails->getSessionId(), $actual);
        $this->assertStringContainsString($bankDetails->getPaymentConfirmationDetails()->getStatusCode(), $actual);
        $this->assertStringContainsString($bankDetails->getPaymentConfirmationDetails()->getPaymentReferenceIdentifier(), $actual);
        $this->assertXmlEqualsFixture('ShopResponseDetailsOK.xml', $actual);
    }

    public function testHandleConfirmationUrlThrowsExceptionWhenCallbackDoesNotReturnTrue(): void
    {
        $this->expectException(CallbackResponseException::class);
        $this->expectExceptionMessage('Confirmation callback must return true');
        $this->handleConfirmation(
            function () { return null; },
            null,
            'BankConfirmationDetailsWithoutSignature.xml'
        );
    }

    /**
     * @throws XmlValidationException
     */
    public function testHandleConfirmationUrlReturnsErrorWhenCallbackDoesNotReturnTrue(): void
    {
        $temp = tempnam(sys_get_temp_dir(), 'SoCommunicatorTest_');
        try {
            $this->handleConfirmation(
                function () {
                    return null;
                },
                null,
                'BankConfirmationDetailsWithoutSignature.xml',
                $temp
            );
        } catch (CallbackResponseException $e) {
            $msg = $e->getMessage();
        }
        $actual = file_get_contents($temp);
        XmlValidator::ValidateEpsProtocol($actual);
        $this->assertStringContainsString($msg, $actual);
        $this->assertXmlEqualsFixture('ShopResponseDetailsError.xml', $actual);
    }

    public function testHandleConfirmationUrlVitalityCheckDoesNotCallBankConfirmationCallback(): void
    {
        $called = false;
        $this->handleConfirmation(
            function () use (&$called) { $called = true; return true; },
            null,
            'VitalityCheckDetails.xml'
        );
        $this->assertFalse($called);
    }

    public function testHandleConfirmationUrlVitalityThrowsExceptionOnInvalidValidationCallback(): void
    {
        $this->expectException(InvalidCallbackException::class);
        $this->expectExceptionMessage('vitalityCheckCallback not callable');
        $this->handleConfirmation(
            function () { return true; },
            "invalid",
            'VitalityCheckDetails.xml'
        );
    }

    /**
     * @throws XmlValidationException
     */
    public function testHandleConfirmationUrlVitalityReturnsErrorOnInvalidValidationCallback(): void
    {
        $temp = tempnam(sys_get_temp_dir(), 'SoCommunicatorTest_');
        try {
            $this->handleConfirmation(
                function () {
                    return true;
                },
                'invalid',
                'VitalityCheckDetails.xml',
                $temp
            );
        } catch (InvalidCallbackException $e) {
            $msg = $e->getMessage();
        }
        $actual = file_get_contents($temp);
        XmlValidator::ValidateEpsProtocol($actual);
        $this->assertStringContainsString($msg, $actual);
        $this->assertXmlEqualsFixture('ShopResponseDetailsErrorVitalityCheckNotCallable.xml', $actual);
    }

    public function testHandleConfirmationUrlVitalityThrowsExceptionWhenCallbackDoesNotReturnTrue(): void
    {
        $this->expectException(CallbackResponseException::class);
        $this->expectExceptionMessage('Vitality check callback must return true');
        $this->handleConfirmation(
            function () {
                return true;
            },
            function () {
                return false;
            },
            'VitalityCheckDetails.xml'
        );
    }

    /**
     * @throws ShopResponseException
     * @throws XmlValidationException
     * @throws InvalidCallbackException
     * @throws CallbackResponseException
     */
    public function testHandleConfirmationUrlVitalityWritesInputToOutputStream(): void
    {
        $temp = tempnam(sys_get_temp_dir(), 'SoCommunicatorTest_');
        $rawXml = file_get_contents($this->fixturePath('VitalityCheckDetails.xml'));
        $this->target->handleConfirmationUrl(
            function () {
                return true;
            },
            function () use ($rawXml) {
                $this->assertNotEmpty($rawXml);
                return true;
            },
            $this->fixturePath('VitalityCheckDetails.xml'),
            $temp
        );
        $this->assertXmlEqualsFixture('VitalityCheckDetails.xml', file_get_contents($temp));
    }

    public function testHandleConfirmationUrlReturnsErrorOnInvalidXml(): void
    {
        $temp = tempnam(sys_get_temp_dir(), 'SoCommunicatorTest_');
        try {
            $this->handleConfirmation(
                function () {
                    return true;
                },
                null,
                'BankConfirmationDetailsInvalid.xml',
                $temp
            );
        } catch (XmlValidationException $e) {
            $msg = $e->getMessage();
        }
        $actual = file_get_contents($temp);
        XmlValidator::ValidateEpsProtocol($actual);
        $this->assertStringContainsString('XML does not validate against XSD.', $msg);
        $this->assertXmlEqualsFixture('ShopResponseDetailsXMLError.xml', $actual);
    }
}
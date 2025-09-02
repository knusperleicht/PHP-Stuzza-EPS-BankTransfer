<?php
declare(strict_types=1);

namespace Knusperleicht\EpsBankTransfer\Tests\Api;

use DateTime;
use Knusperleicht\EpsBankTransfer\Domain\BankConfirmationDetails;
use Knusperleicht\EpsBankTransfer\Domain\VitalityCheckDetails;
use Knusperleicht\EpsBankTransfer\Exceptions\CallbackResponseException;
use Knusperleicht\EpsBankTransfer\Exceptions\EpsException;
use Knusperleicht\EpsBankTransfer\Exceptions\InvalidCallbackException;
use Knusperleicht\EpsBankTransfer\Exceptions\XmlValidationException;
use Knusperleicht\EpsBankTransfer\Tests\Helper\SoCommunicatorTestTrait;
use Knusperleicht\EpsBankTransfer\Utilities\XmlValidator;
use PHPUnit\Framework\TestCase;

class ConfirmationCallbackV27Test extends TestCase
{
    use SoCommunicatorTestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpCommunicator();
    }

    private function handleConfirmation(
        ?callable $confirmationCallback,
        ?callable $vitalityCheckCallback,
        string $xmlFile,
        ?string $outputFile = null
    ): void {
        $dataPath = $this->fixturePath($xmlFile);
        $this->target->handleConfirmationUrl(
            $confirmationCallback,
            $vitalityCheckCallback,
            $dataPath,
            $outputFile ?? 'php://temp',
            '2.7'
        );
    }

    public function testHandleConfirmationUrlThrowsExceptionOnMissingCallback(): void
    {
        $this->expectException(InvalidCallbackException::class);
        $this->expectExceptionMessage('ConfirmationCallback not callable or missing');
        $this->handleConfirmation(null, null, 'V27/BankConfirmationDetailsWithoutSignature.xml');
    }

    public function testHandleConfirmationUrlReturnsErrorOnMissingCallback(): void
    {
        $temp = tempnam(sys_get_temp_dir(), 'SoCommunicatorTest_');
        try {
            $this->handleConfirmation(null, null, 'V27/BankConfirmationDetailsWithoutSignature.xml', $temp);
        } catch (InvalidCallbackException $e) {
            $msg = $e->getMessage();
        } finally {
            $actual = file_get_contents($temp);
            $this->assertStringContainsString($msg, $actual);
            XmlValidator::validateEpsProtocol($actual, '2.7');
            @unlink($temp);
        }
    }

    public function testHandleConfirmationUrlThrowsExceptionOnInvalidXml(): void
    {
        $this->expectException(XmlValidationException::class);
        $this->handleConfirmation(
            function () { return true; },
            null,
            'V27/BankConfirmationDetailsInvalid.xml'
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
            'V27/BankConfirmationDetailsWithoutSignature.xml'
        );

        $expected = file_get_contents($this->fixturePath('V27/BankConfirmationDetailsWithoutSignature.xml'));
        $this->assertSame($expected, $actual);
    }

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
            'V27/BankConfirmationDetailsWithoutSignature.xml',
            $temp
        );

        $expectedBankDetails = new BankConfirmationDetails(
            '13212452dea',
            'AT1234567890XYZ',
            'AAAAAAAAAAA',
            new DateTime('2007-03-19T11:11:00.000000-0500'),
            '120000302122320812201106461',
            'OK'
        );
        $this->assertEquals($expectedBankDetails, $bankDetails);

        $actual = file_get_contents($temp);
        $this->assertXmlEqualsFixture('V27/ShopResponseDetailsOK.xml', $actual);
        XmlValidator::validateEpsProtocol($actual, '2.7');
    }

    public function testHandleConfirmationUrlThrowsExceptionWhenCallbackDoesNotReturnTrue(): void
    {
        $this->expectException(CallbackResponseException::class);
        $this->expectExceptionMessage('Confirmation callback must return true');
        $this->handleConfirmation(
            function () { return null; },
            null,
            'V27/BankConfirmationDetailsWithoutSignature.xml'
        );
    }

    public function testHandleConfirmationUrlReturnsErrorWhenCallbackDoesNotReturnTrue(): void
    {
        $temp = tempnam(sys_get_temp_dir(), 'SoCommunicatorTest_');
        try {
            $this->handleConfirmation(
                function () { return null; },
                null,
                'V27/BankConfirmationDetailsWithoutSignature.xml',
                $temp
            );
        } catch (CallbackResponseException $e) {
            $msg = $e->getMessage();
        } finally {
            $actual = file_get_contents($temp);
            $this->assertStringContainsString($msg, $actual);
            $this->assertXmlEqualsFixture('V27/ShopResponseDetailsError.xml', $actual);
            XmlValidator::validateEpsProtocol($actual, '2.7');
            @unlink($temp);
        }
    }

    public function testHandleConfirmationUrlVitalityCheckDoesNotCallBankConfirmationCallback(): void
    {
        $called = false;
        $this->handleConfirmation(
            function () use (&$called) { $called = true; return true; },
            null,
            'V27/VitalityCheckDetails.xml'
        );
        $this->assertFalse($called);
    }

    public function testHandleConfirmationUrlVitalityThrowsExceptionWhenCallbackDoesNotReturnTrue(): void
    {
        $this->expectException(CallbackResponseException::class);
        $this->expectExceptionMessage('Vitality check callback must return true');
        $this->handleConfirmation(
            function () { return true; },
            function () { return false; },
            'V27/VitalityCheckDetails.xml'
        );
    }

    public function testHandleConfirmationUrlVitalityWritesInputToOutputStream(): void
    {
        $vitalityDetails = null;
        $temp = tempnam(sys_get_temp_dir(), 'SoCommunicatorTest_');
        $rawXml = file_get_contents($this->fixturePath('V27/VitalityCheckDetails.xml'));
        $this->target->handleConfirmationUrl(
            function () { return true; },
            function ($xml, $vc) use ($rawXml, &$vitalityDetails) {
                $this->assertSame($rawXml, $xml);
                $vitalityDetails = $vc;
                return true;
            },
            $this->fixturePath('V27/VitalityCheckDetails.xml'),
            $temp,
            '2.7'
        );

        $expectedVitalityDetails = new VitalityCheckDetails(null, 'AT1234567890XYZ', 'AT12ABCDEFGH');
        $this->assertEquals($expectedVitalityDetails, $vitalityDetails);

        $actual = file_get_contents($temp);
        XmlValidator::validateEpsProtocol($actual, '2.7');
        $this->assertXmlEqualsFixture('V27/VitalityCheckDetails.xml', file_get_contents($temp));
    }

    public function testHandleConfirmationUrlReturnsErrorOnInvalidXml(): void
    {
        $temp = tempnam(sys_get_temp_dir(), 'SoCommunicatorTest_');
        $this->expectException(EpsException::class);
        $this->expectExceptionMessage('XML does not validate against XSD');
        try {
            $this->handleConfirmation(
                function () { return true; },
                null,
                'V27/BankConfirmationDetailsInvalid.xml',
                $temp
            );
        } finally {
            $actual = file_get_contents($temp);
            $this->assertXmlEqualsFixture('V27/ShopResponseDetailsXMLError.xml', $actual);
            XmlValidator::validateEpsProtocol($actual);
            @unlink($temp);
        }
    }

    public function testHandleConfirmationUrlWithBankConfirmationNOK(): void
    {
        $bankDetails = null;
        $temp = tempnam(sys_get_temp_dir(), 'SoCommunicatorTest_');
        $this->handleConfirmation(
            function ($raw, $bc) use (&$bankDetails) {
                $bankDetails = $bc;
                return true;
            },
            null,
            'V27/BankConfirmationNOK.xml',
            $temp
        );

        $expectedBankDetails = new BankConfirmationDetails(
            '13212452dea',
            'AT1234567890XYZ',
            'AAAAAAAAAAA',
            new DateTime('2007-03-19T11:11:00.000000-0500'),
            '120000302122320812201106461',
            'NOK',
            '123',
            'Something went wrong',
            'SO'
        );
        $this->assertEquals($expectedBankDetails, $bankDetails);

        $actual = file_get_contents($temp);
        $this->assertXmlEqualsFixture('V27/ShopResponseDetailsNOK.xml', $actual);
        XmlValidator::validateEpsProtocol($actual, '2.7');
    }
}
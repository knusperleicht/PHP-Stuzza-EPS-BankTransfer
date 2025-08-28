<?php
declare(strict_types=1);

namespace Externet\EpsBankTransfer\Tests\Api;

use Externet\EpsBankTransfer\Exceptions\CallbackResponseException;
use Externet\EpsBankTransfer\Exceptions\InvalidCallbackException;
use Externet\EpsBankTransfer\Exceptions\XmlValidationException;
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

    public function testHandleConfirmationUrlCallsCallbackWithBankConfirmationDetails(): void
    {
        $bankDetails = null;
        $this->handleConfirmation(
            function ($raw, $bc) use (&$bankDetails) {
                $bankDetails = $bc;
                return true;
            },
            null,
            'BankConfirmationDetailsWithoutSignature.xml'
        );

        $this->assertEquals('AT1234567890XYZ', $bankDetails->GetRemittanceIdentifier());
        $this->assertEquals('OK', $bankDetails->GetStatusCode());
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

    public function testHandleConfirmationUrlReturnsErrorWhenCallbackDoesNotReturnTrue(): void
    {
        $temp = tempnam(sys_get_temp_dir(), 'SoCommunicatorTest_');
        try {
            $this->handleConfirmation(
                function () { return null; },
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

    public function testHandleConfirmationUrlVitalityReturnsErrorOnInvalidValidationCallback(): void
    {
        $temp = tempnam(sys_get_temp_dir(), 'SoCommunicatorTest_');
        try {
            $this->handleConfirmation(
                function () { return true; },
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
    }

    public function testHandleConfirmationUrlVitalityThrowsExceptionWhenCallbackDoesNotReturnTrue(): void
    {
        $this->expectException(CallbackResponseException::class);
        $this->expectExceptionMessage('Vitality check callback must return true');
        $this->handleConfirmation(
            function () { return true; },
            function () { return null; },
            'VitalityCheckDetails.xml'
        );
    }

    public function testHandleConfirmationUrlVitalityWritesInputToOutputStream(): void
    {
        $temp = tempnam(sys_get_temp_dir(), 'SoCommunicatorTest_');
        $this->target->handleConfirmationUrl(
            function () { return true; },
            null,
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
                function () { return true; },
                null,
                'BankConfirmationDetailsInvalid.xml',
                $temp
            );
        } catch (XmlValidationException $e) {
            // expected
        }
        $actual = file_get_contents($temp);
        XmlValidator::ValidateEpsProtocol($actual);
        $this->assertStringContainsString('Error occurred during XML validation', $actual);
    }
}
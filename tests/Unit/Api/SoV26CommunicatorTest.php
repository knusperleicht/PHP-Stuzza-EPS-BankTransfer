<?php
declare(strict_types=1);

namespace Externet\EpsBankTransfer\Tests\Api;

use Exception;
use Externet\EpsBankTransfer\Api\SoV26Communicator;
use Externet\EpsBankTransfer\Exceptions\CallbackResponseException;
use Externet\EpsBankTransfer\Exceptions\InvalidCallbackException;
use Externet\EpsBankTransfer\Exceptions\XmlValidationException;
use Externet\EpsBankTransfer\Generated\BankList\EpsSOBankListProtocol;
use Externet\EpsBankTransfer\Requests\InitiateTransferRequest;
use Externet\EpsBankTransfer\Requests\Parts\PaymentFlowUrls;
use Externet\EpsBankTransfer\Requests\RefundRequest;
use Externet\EpsBankTransfer\Tests\Helper\Psr18TestHttp;
use Externet\EpsBankTransfer\Tests\Helper\XmlFixtureTestTrait;
use Externet\EpsBankTransfer\Utilities\XmlValidator;
use GuzzleHttp\Psr7\HttpFactory;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use UnexpectedValueException;

class SoV26CommunicatorTest extends TestCase
{
    use XmlFixtureTestTrait;

    /** @var SoV26Communicator */
    private $target;
    /** @var Psr18TestHttp */
    private $http;

    protected function setUp(): void
    {
        parent::setUp();
        $this->http = new Psr18TestHttp();
        $factory = new HttpFactory();
        $this->target = new SoV26Communicator($this->http, $factory, $factory, SoV26Communicator::LIVE_MODE_URL);
        date_default_timezone_set('UTC');
    }

    private function mockResponse(int $status, string $body, array $headers = ['Content-Type' => 'application/xml']): void
    {
        $this->http->pushResponse($status, $headers, $body);
    }

    /**
     * @throws XmlValidationException
     */
    public function testGetBanksSuccess(): void
    {
        $this->mockResponse(200, $this->loadFixture('BankListSample.xml'));;
        $banks = $this->target->getBanks();
        $this->assertInstanceOf(EpsSOBankListProtocol::class, $banks);
    }

    /**
     * @dataProvider provideBankUrls
     * @throws XmlValidationException
     */
    public function testGetBanksCallsCorrectUrl(string $modeUrl, string $expectedUrl): void
    {
        $factory = new HttpFactory();
        $this->target = new SoV26Communicator($this->http, $factory, $factory, $modeUrl);
        $this->mockResponse(200, $this->loadFixture('BankListSample.xml'));
        $this->target->getBanks();

        $this->assertEquals($expectedUrl, $this->http->getLastRequestInfo()['url']);
    }

    public static function provideBankUrls(): array
    {
        return [
            'live' => [SoV26Communicator::LIVE_MODE_URL, 'https://routing.eps.or.at/appl/epsSO/data/haendler/v2_6'],
            'test' => [SoV26Communicator::TEST_MODE_URL, 'https://routing-test.eps.or.at/appl/epsSO/data/haendler/v2_6'],
        ];
    }

    /**
     * @throws XmlValidationException
     */
    public function testGetBankListReadError(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('GET https://routing.eps.or.at/appl/epsSO/data/haendler/v2_6 failed with HTTP 404');
        $this->mockResponse(404, 'Not found', ['Content-Type' => 'text/plain']);
        $this->target->getBanks();
    }

    public function testSendTransferInitiatorDetailsThrowsValidationException(): void
    {
        $this->expectException(XmlValidationException::class);
        $this->expectExceptionMessage('Failed to load XML: DOMDocument::loadXML(): Start tag expected, \'<\' not found in Entity, line: 1');
        $this->mockResponse(200, 'invalidData');
        $this->target->sendTransferInitiatorDetails($this->getMockedTransferInitiatorDetails());
    }

    /**
     * @throws XmlValidationException
     */
    public function testSendTransferInitiatorDetailsToCorrectUrl(): void
    {
        $this->mockResponse(200, $this->loadFixture('BankResponseDetails004.xml'));
        $this->target->sendTransferInitiatorDetails($this->getMockedTransferInitiatorDetails());

        $this->assertEquals(
            'https://routing.eps.or.at/appl/epsSO/transinit/eps/v2_6',
            $this->http->getLastRequestInfo()['url']
        );
    }

    /**
     * @throws XmlValidationException
     */
    public function testSendTransferInitiatorDetailsToTestUrl(): void
    {
        $factory = new HttpFactory();
        $this->target = new SoV26Communicator($this->http, $factory, $factory, SoV26Communicator::TEST_MODE_URL);

        $this->mockResponse(200, $this->loadFixture('BankResponseDetails004.xml'));
        $this->target->sendTransferInitiatorDetails($this->getMockedTransferInitiatorDetails());

        $this->assertEquals(
            'https://routing-test.eps.or.at/appl/epsSO/transinit/eps/v2_6',
            $this->http->getLastRequestInfo()['url']
        );
    }

    /**
     * @throws XmlValidationException
     */
    public function testOverrideDefaultBaseUrl(): void
    {
        $this->target->setBaseUrl('http://example.com');

        $this->mockResponse(200, $this->loadFixture('BankListSample.xml'));
        $this->target->getBanks();
        $this->assertEquals('http://example.com/data/haendler/v2_6', $this->http->getLastRequestInfo()['url']);

        $this->mockResponse(200, $this->loadFixture('BankResponseDetails004.xml'));
        $this->target->sendTransferInitiatorDetails($this->getMockedTransferInitiatorDetails());
        $this->assertEquals('http://example.com/transinit/eps/v2_6', $this->http->getLastRequestInfo()['url']);
    }

    /**
     * @throws XmlValidationException
     */
    public function testSendTransferInitiatorDetailsThrowsExceptionOn404(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('POST https://routing.eps.or.at/appl/epsSO/transinit/eps/v2_6 failed with HTTP 404');
        $this->mockResponse(404, 'Not found', ['Content-Type' => 'text/plain']);
        $this->target->sendTransferInitiatorDetails($this->getMockedTransferInitiatorDetails());
    }

    /**
     * @throws XmlValidationException
     */
    public function testSendTransferInitiatorDetailsWithPreselectedBank(): void
    {
        $url = 'https://routing.eps.or.at/appl/epsSO/transinit/eps/v2_6/23ea3d14-278c-4e81-a021-d7b77492b611';
        $this->mockResponse(200, $this->loadFixture('BankResponseDetails000.xml'));

        $this->target->sendTransferInitiatorDetails($this->getMockedTransferInitiatorDetails(), $url);

        $this->assertEquals($url, $this->http->getLastRequestInfo()['url']);
    }

    /**
     * @throws XmlValidationException
     */
    public function testSendTransferInitiatorDetailsWithSecurityThrowsExceptionOnEmptySalt(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('No security seed set when using security suffix.');
        $this->target->setObscuritySuffixLength(8);
        $this->mockResponse(200, $this->loadFixture('BankResponseDetails000.xml'));
        $this->target->sendTransferInitiatorDetails($this->getMockedTransferInitiatorDetails());
    }

    /**
     * @throws XmlValidationException
     */
    public function testSendTransferInitiatorDetailsWithSecurityAppendsHash(): void
    {
        $this->target->setObscuritySuffixLength(8);
        $this->target->setObscuritySeed('Some seed');
        $this->mockResponse(200, $this->loadFixture('BankResponseDetails000.xml'));

        $t = new PaymentFlowUrls('a', 'b', 'c');
        $transferInitiatorDetails = new InitiateTransferRequest('a', 'b', 'c', 'd', 'e', 'f', 0, $t);
        $transferInitiatorDetails->remittanceIdentifier = 'Order1';

        $url = 'https://routing.eps.or.at/appl/epsSO/transinit/eps/v2_6/someid';
        $this->target->sendTransferInitiatorDetails($transferInitiatorDetails, $url);

        $body = $this->http->getLastRequestInfo()['body'];
        $this->assertStringContainsString('Order1', $body);
    }

    // === Callback Tests ===

    private function handleConfirmation(
        ?callable $bankCallback,
                  $vitalityCallback,
        string $xmlFile,
        ?string $outputFile = null
    ): void {
        $dataPath = $this->fixturePath($xmlFile);
        $this->target->handleConfirmationUrl($bankCallback, $vitalityCallback, $dataPath, $outputFile ?? 'php://temp');
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
        $this->expectExceptionMessage('XML does not validate against XSD.');
        $this->handleConfirmation(function () {
            return true;
        }, null, 'BankConfirmationDetailsInvalid.xml');
    }

    public function testHandleConfirmationUrlCallsCallback(): void
    {
        $actual = 'Nothing';
        $this->handleConfirmation(function ($data) use (&$actual) {
            $actual = $data;
            return true;
        }, null, 'BankConfirmationDetailsWithoutSignature.xml');

        $expected = file_get_contents($this->fixturePath('BankConfirmationDetailsWithoutSignature.xml'));
        $this->assertSame($expected, $actual);
    }

    public function testHandleConfirmationUrlCallsCallbackWithBankConfirmationDetails(): void
    {
        $bankDetails = null;
        $this->handleConfirmation(function ($raw, $bc) use (&$bankDetails) {
            $bankDetails = $bc;
            return true;
        }, null, 'BankConfirmationDetailsWithoutSignature.xml');

        $this->assertEquals('AT1234567890XYZ', $bankDetails->GetRemittanceIdentifier());
        $this->assertEquals('OK', $bankDetails->GetStatusCode());
    }

    public function testHandleConfirmationUrlThrowsExceptionWhenCallbackDoesNotReturnTrue(): void
    {
        $this->expectException(CallbackResponseException::class);
        $this->expectExceptionMessage('Confirmation callback must return true');
        $this->handleConfirmation(function () {
            return null;
        }, null, 'BankConfirmationDetailsWithoutSignature.xml');
    }

    /**
     * @throws XmlValidationException
     */
    public function testHandleConfirmationUrlReturnsErrorWhenCallbackDoesNotReturnTrue(): void
    {
        $temp = tempnam(sys_get_temp_dir(), 'SoCommunicatorTest_');
        try {
            $this->handleConfirmation(function () {
                return null;
            }, null, 'BankConfirmationDetailsWithoutSignature.xml', $temp);
        } catch (CallbackResponseException $e) {
            $msg = $e->getMessage();
        }

        $actual = file_get_contents($temp);
        XmlValidator::ValidateEpsProtocol($actual);

        $this->assertStringContainsString('ShopResponseDetails>', $actual);
        $this->assertStringContainsString('ErrorMsg>', $actual);
        $this->assertStringContainsString($msg, $actual);
    }

    public function testHandleConfirmationUrlVitalityCheckDoesNotCallBankConfirmationCallback(): void
    {
        $called = false;
        $this->handleConfirmation(function () use (&$called) { $called = true; }, null, 'VitalityCheckDetails.xml');
        $this->assertFalse($called);
    }

    public function testHandleConfirmationUrlVitalityThrowsExceptionOnInvalidValidationCallback(): void
    {
        $this->expectException(InvalidCallbackException::class);
        $this->expectExceptionMessage('vitalityCheckCallback not callable');
        $this->handleConfirmation(
            function () {
                return true;
            },
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

        $msg = null;
        try {
            $this->handleConfirmation(function () {
                return true;
            }, 'invalid', 'VitalityCheckDetails.xml', $temp);
        } catch (InvalidCallbackException $e) {
            $msg = $e->getMessage();
        }

        $this->assertNotNull($msg, 'Expected InvalidCallbackException was not thrown.');

        $actual = file_get_contents($temp);
        XmlValidator::ValidateEpsProtocol($actual);
        $this->assertStringContainsString($msg, $actual);

        @unlink($temp); // cleanup
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
                return null;
            },
            'VitalityCheckDetails.xml'
        );
    }

    /**
     * @throws Exception
     */
    public function testHandleConfirmationUrlVitalityWritesInputToOutputStream(): void
    {
        $temp = tempnam(sys_get_temp_dir(), 'SoCommunicatorTest_');

        $this->target->handleConfirmationUrl(
            function () {
                return true;
            },
            null,
            $this->fixturePath('VitalityCheckDetails.xml'),
            $temp
        );

        $this->assertXmlEqualsFixture('VitalityCheckDetails.xml', file_get_contents($temp));

        @unlink($temp); // cleanup
    }

    /**
     * @throws XmlValidationException
     */
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

        $this->assertStringContainsString('ShopResponseDetails>', $actual);
        $this->assertStringContainsString('Error occurred during XML validation', $actual);

        @unlink($temp); // cleanup
    }
    
    private function getMockedTransferInitiatorDetails(): InitiateTransferRequest
    {
        $t = new PaymentFlowUrls(
            'https://example.com/confirmation',
            'https://example.com/success',
            'https://example.com/failure'
        );

        $ti = new InitiateTransferRequest(
            'TestShop', 'secret123', 'TESTBANKXXX',
            'Test Company GmbH', 'AT611904300234573201', 'REF123456789',
            12050, $t
        );

        $ti->remittanceIdentifier = 'orderid';
        return $ti;
    }

    /**
     * Test that SendRefundRequest throws XML validation exception on invalid data
     *
     * @return void
     * @throws XmlValidationException
     * @throws Exception
     */
    function testSendRefundRequestThrowsValidationException(): void
    {
        $refundRequest = $this->getMockedRefundRequest();
        $this->http->pushResponse(200, array('Content-Type' => 'application/xml'), 'invalidData');

        $this->expectException(XmlValidationException::class);
        $this->expectExceptionMessage('Failed to load XML: DOMDocument::loadXML(): Start tag expected, \'<\' not found in Entity, line: 1');
        $this->target->sendRefundRequest($refundRequest);
    }

    /**
     * Test that SendRefundRequest sends to the correct production URL
     *
     * @return void
     * @throws Exception
     */
    function testSendRefundRequestToCorrectUrl(): void
    {
        $refundRequest = $this->getMockedRefundRequest();
        $this->http->pushResponse(200, array('Content-Type' => 'application/xml'), $this->loadFixture('RefundResponseAccepted000.xml'));

        $this->target->sendRefundRequest($refundRequest);

        $info = $this->http->getLastRequestInfo();
        $this->assertEquals('https://routing.eps.or.at/appl/epsSO/refund/eps/v2_6', $info['url']);
    }

    /**
     * Test that SendRefundRequest sends to the test URL in test mode
     *
     * @return void
     * @throws Exception
     */
    function testSendRefundRequestToTestUrl(): void
    {
        $this->target = new SoV26Communicator($this->http, new HttpFactory(), new HttpFactory(), SoV26Communicator::TEST_MODE_URL);
        $refundRequest = $this->getMockedRefundRequest();
        $this->http->pushResponse(200, array('Content-Type' => 'application/xml'), $this->loadFixture('RefundResponseAccepted000.xml'));

        $this->target->sendRefundRequest($refundRequest);

        $info = $this->http->getLastRequestInfo();
        $this->assertEquals('https://routing-test.eps.or.at/appl/epsSO/refund/eps/v2_6', $info['url']);
    }

    private function getMockedRefundRequest(): RefundRequest
    {
        return new RefundRequest(
            '2025-02-10T15:30:00',
            '1234567890',
            'AT611904300234573201',
            '100.50',
            'EUR',
            'TestUserId',
            'secret123',
            'Duplicate transaction'
        );
    }
}

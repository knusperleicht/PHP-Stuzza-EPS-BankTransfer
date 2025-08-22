<?php

/**
 * Tests for the SoCommunicator class which implements the eps payment communication
 */

namespace at\externet\eps_bank_transfer;

use unit\at\externet\eps_bank_transfer\Psr18TestHttp;
use GuzzleHttp\Psr7\HttpFactory;

require_once __DIR__ . DIRECTORY_SEPARATOR . 'Psr18TestHttp.php';

class SoCommunicatorTest extends BaseTest
{

    /** @var SoCommunicator Instance under test */
    private $target;

    /** @var Psr18TestHttp HTTP client mock */
    private $http;

    /** @var RequestMock Request response dummy */
    private $httpResponseDummy;

    /**
     * Test setup method run before each test
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->http = new Psr18TestHttp();
        $requestFactory = new HttpFactory();
        $streamFactory = new HttpFactory();
        $this->target = new SoCommunicator(false, $this->http, $requestFactory, $streamFactory);
        date_default_timezone_set('UTC');
    }

    /**
     * Test that GetBanks does not throw validation errors when validation is disabled
     */
    public function testGetBanksNoExceptionWhenNoValidation()
    {
        $this->http->pushResponse(200, array('Content-Type' => 'application/xml'), 'bar');
        $banks = $this->target->GetBanks(false);
        $this->assertEquals('bar', $banks);
    }

    /**
     * Test that GetBanks calls the correct bank list URL
     */
    public function testGetBanksCallsCorrectUrl()
    {
        $this->http->pushResponse(200, array('Content-Type' => 'application/xml'), 'bar');
        $this->target->GetBanks(false);
        $info = $this->http->getLastRequestInfo();
        $this->assertEquals('https://routing.eps.or.at/appl/epsSO/data/haendler/v2_6', $info['url']);
    }

    /**
     * Test that GetBanksArray returns the expected bank data structure
     */
    public function testGetBanksArray()
    {
        $this->http->pushResponse(200, array('Content-Type' => 'application/xml'), $this->GetEpsData('BankListSample.xml'));

        $actual = $this->target->GetBanksArray();
        $expected = array(
            'Testbank' => array(
                'bic' => 'TESTBANKXXX',
                'bezeichnung' => 'Testbank',
                'land' => 'AT',
                'epsUrl' => 'https://routing.eps.or.at/appl/epsSO/transinit/eps/v2_6/23ea3d14-278c-4e81-a021-d7b77492b611'
            )
        );

        $this->assertEquals($expected, $actual);
    }

    /**
     * Test that GetBanks throws exception on read error
     */
    public function testGetBanklistReadError()
    {
        $this->expectException(\RuntimeException::class);
        $this->http->pushResponse(404, array('Content-Type' => 'text/plain'), 'Not found');
        $this->target->GetBanks();
    }

    /**
     * Test that TryGetBanksArray returns null when banks cannot be retrieved
     */
    public function testTryGetBanksArrayReturnsNull()
    {
        $banks = $this->target->TryGetBanksArray();
        $this->assertEquals($banks, null);
    }

    /**
     * Test that TryGetBanksArray returns bank data when available
     */
    public function testTryGetBanksArrayReturnsBanks()
    {
        $this->http->pushResponse(200, array('Content-Type' => 'application/xml'), $this->GetEpsData('BankListSample.xml'));

        $actual = $this->target->TryGetBanksArray();
        $expected = array(
            'Testbank' => array(
                'bic' => 'TESTBANKXXX',
                'bezeichnung' => 'Testbank',
                'land' => 'AT',
                'epsUrl' => 'https://routing.eps.or.at/appl/epsSO/transinit/eps/v2_6/23ea3d14-278c-4e81-a021-d7b77492b611'
            )
        );

        $this->assertEquals($expected, $actual);
    }

    /**
     * Test that SendTransferInitiatorDetails throws validation exception on invalid data
     */
    public function testSendTransferInitiatorDetailsThrowsValidationException()
    {
        $transferInitiatorDetails = $this->getMockedTransferInitiatorDetails();
        $this->http->pushResponse(200, array('Content-Type' => 'application/xml'), 'invalidData');

        $this->expectException(XmlValidationException::class);
        $this->target->SendTransferInitiatorDetails($transferInitiatorDetails);
    }

    /**
     * Test that SendTransferInitiatorDetails sends to correct URL
     */
    public function testSendTransferInitiatorDetailsToCorrectUrl()
    {
        $transferInitiatorDetails = $this->getMockedTransferInitiatorDetails();
        $this->http->pushResponse(200, array('Content-Type' => 'application/xml'), $this->GetEpsData('BankResponseDetails004.xml'));

        $this->target->SendTransferInitiatorDetails($transferInitiatorDetails);

        $info = $this->http->getLastRequestInfo();
        $this->assertEquals('https://routing.eps.or.at/appl/epsSO/transinit/eps/v2_6', $info['url']);
    }

    /**
     * Test that SendTransferInitiatorDetails sends to test URL in test mode
     */
    public function testSendTransferInitiatorDetailsToTestUrl()
    {
        $this->target = new SoCommunicator(true, $this->http, new HttpFactory(), new HttpFactory());
        $transferInitiatorDetails = $this->getMockedTransferInitiatorDetails();
        $this->http->pushResponse(200, array('Content-Type' => 'application/xml'), $this->GetEpsData('BankResponseDetails004.xml'));

        $this->target->SendTransferInitiatorDetails($transferInitiatorDetails);

        $info = $this->http->getLastRequestInfo();
        $this->assertEquals('https://routing.eps.or.at/appl/epsSO-test/transinit/eps/v2_6', $info['url']);
    }

    /**
     * Test that base URL can be overridden
     */
    public function testOverrideDefaultBaseUrl()
    {
        $this->target->BaseUrl = 'http://example.com';

        $this->http->pushResponse(200, array('Content-Type' => 'application/xml'), $this->GetEpsData('BankListSample.xml'));
        $this->target->GetBanksArray();
        $info = $this->http->getLastRequestInfo();
        $this->assertEquals('http://example.com/data/haendler/v2_6', $info['url']);

        $transferInitiatorDetails = $this->getMockedTransferInitiatorDetails();
        $this->http->pushResponse(200, array('Content-Type' => 'application/xml'), $this->GetEpsData('BankResponseDetails004.xml'));

        $this->target->SendTransferInitiatorDetails($transferInitiatorDetails);
        $info = $this->http->getLastRequestInfo();
        $this->assertEquals('http://example.com/transinit/eps/v2_6', $info['url']);
    }

    /**
     * Test that SendTransferInitiatorDetails throws exception on 404
     */
    public function testSendTransferInitiatorDetailsThrowsExceptionOn404()
    {
        $transferInitiatorDetails = $this->getMockedTransferInitiatorDetails();
        $this->http->pushResponse(404, array('Content-Type' => 'text/plain'), 'Not found');
        $this->expectException(\RuntimeException::class);

        $this->target->SendTransferInitiatorDetails($transferInitiatorDetails);
    }

    /**
     * Test that SendTransferInitiatorDetails works with preselected bank
     */
    public function testSendTransferInitiatorDetailsWithPreselectedBank()
    {
        $url = 'https://routing.eps.or.at/appl/epsSO/transinit/eps/v2_6/23ea3d14-278c-4e81-a021-d7b77492b611';
        $transferInitiatorDetails = $this->getMockedTransferInitiatorDetails();
        $this->http->pushResponse(200, array('Content-Type' => 'application/xml'), $this->GetEpsData('BankResponseDetails000.xml'));

        $this->target->SendTransferInitiatorDetails($transferInitiatorDetails, $url);

        $info = $this->http->getLastRequestInfo();
        $this->assertEquals($url, $info['url']);
    }

    /**
     * Test that empty security salt throws exception
     */
    public function testSendTransferInitiatorDetailsWithSecurityThrowsExceptionOnEmptySalt()
    {
        $url = 'https://routing.eps.or.at/appl/epsSO/transinit/eps/v2_6/23ea3d14-278c-4e81-a021-d7b77492b611';
        $transferInitiatorDetails = $this->getMockedTransferInitiatorDetails();
        $this->http->pushResponse(200, array('Content-Type' => 'application/xml'), $this->GetEpsData('BankResponseDetails000.xml'));
        $this->target->ObscuritySuffixLength = 8;
        $this->expectException(\UnexpectedValueException::class);

        $this->target->SendTransferInitiatorDetails($transferInitiatorDetails, $url);
    }

    /**
     * Test that security hash is appended to order ID
     */
    public function testSendTransferInitiatorDetailsWithSecurityAppendsHash()
    {
        $this->http->pushResponse(200, array('Content-Type' => 'application/xml'), $this->GetEpsData('BankResponseDetails000.xml'));
        $this->target->ObscuritySuffixLength = 8;
        $this->target->ObscuritySeed = 'Some seed';

        $t = new TransferMsgDetails('a', 'b', 'c');
        $transferInitiatorDetails = new TransferInitiatorDetails('a', 'b', 'c', 'd', 'e', 'f', 0, $t);
        $transferInitiatorDetails->RemittanceIdentifier = 'Order1';

        $url = 'https://routing.eps.or.at/appl/epsSO/transinit/eps/v2_6/23ea3d14-278c-4e81-a021-d7b77492b611';
        $this->target->SendTransferInitiatorDetails($transferInitiatorDetails, $url);

        $info = $this->http->getLastRequestInfo();
        $this->assertEquals('string', gettype($info['body']));
        $this->assertStringContainsString('>Order1U294bWR3<', $info['body']);
    }

    /**
     * Test that missing callback causes exception
     */
    public function testHandleConfirmationUrlThrowsExceptionOnMissingCallback()
    {
        $this->expectException(InvalidCallbackException::class);
        $this->target->HandleConfirmationUrl(null, null, null, 'php://temp');
    }

    /**
     * Test that error is returned on missing callback
     */
    public function testHandleConfirmationUrlReturnsErrorOnMissingCallback()
    {
        $temp = tempnam(sys_get_temp_dir(), 'SoCommunicatorTest_');
        $message = null;
        try {
            $this->target->HandleConfirmationUrl(null, null, null, $temp);
        } catch (\at\externet\eps_bank_transfer\InvalidCallbackException $e) {
            $message = $e->getMessage();
        }
        $actual = file_get_contents($temp);
        XmlValidator::ValidateEpsProtocol($actual);
        $this->assertNotEmpty($message);
        $this->assertStringContainsString($message, $actual);
    }

    /**
     * Test that invalid XML throws validation exception
     */
    public function testHandleConfirmationUrlThrowsExceptionOnInvalidXml()
    {
        $dataPath = $this->GetEpsDataPath('BankConfirmationDetailsInvalid.xml');
        $this->expectException(XmlValidationException::class);
        $this->target->HandleConfirmationUrl(function () {
        }, null, $dataPath, 'php://temp');
    }

    /**
     * Test that callback is called with confirmation data
     */
    public function testHandleConfirmationUrlCallsCallback()
    {
        $dataPath = $this->GetEpsDataPath('BankConfirmationDetailsWithoutSignature.xml');
        $actual = 'Nothing';
        $this->target->HandleConfirmationUrl(function ($data) use (&$actual) {
            $actual = $data;
            return true;
        }, null, $dataPath, 'php://temp');
        $expected = file_get_contents($dataPath);
        $this->assertEquals($actual, $expected);
    }

    /**
     * Test that callback gets BankConfirmationDetails object
     */
    public function testHandleConfirmationUrlCallsCallbackWithBankConfirmationDetails()
    {
        $dataPath = $this->GetEpsDataPath('BankConfirmationDetailsWithoutSignature.xml');
        $bankConfirmationDetails = null;
        $this->target->HandleConfirmationUrl(function ($data, $bc) use (&$bankConfirmationDetails) {
            $bankConfirmationDetails = $bc;
            return true;
        }, null, $dataPath, 'php://temp');

        $this->assertEquals('AT1234567890XYZ', $bankConfirmationDetails->GetRemittanceIdentifier());
        $this->assertEquals('OK', $bankConfirmationDetails->GetStatusCode());
    }

    /**
     * Test that exception is thrown when callback doesn't return true
     */
    public function testHandleConfirmationUrlThrowsExceptionWhenCallbackDoesNotReturnTrue()
    {
        $dataPath = $this->GetEpsDataPath('BankConfirmationDetailsWithoutSignature.xml');
        $this->expectException(CallbackResponseException::class);
        $this->target->HandleConfirmationUrl(function ($data) {
        }, null, $dataPath, 'php://temp');
    }

    /**
     * Test that error is returned when callback doesn't return true
     */
    public function testHandleConfirmationUrlReturnsErrorWhenCallbackDoesNotReturnTrue()
    {
        $dataPath = $this->GetEpsDataPath('BankConfirmationDetailsWithoutSignature.xml');
        $temp = tempnam(sys_get_temp_dir(), 'SoCommunicatorTest_');
        $message = null;
        try {
            $this->target->HandleConfirmationUrl(function ($data) {
            }, null, $dataPath, $temp);
        } catch (\at\externet\eps_bank_transfer\CallbackResponseException $e) {
            $message = $e->getMessage();
        }

        $actual = file_get_contents($temp);
        XmlValidator::ValidateEpsProtocol($actual);

        $this->assertNotEmpty($message);
        $this->assertStringContainsString('ShopResponseDetails>', $actual);
        $this->assertStringContainsString('ErrorMsg>', $actual);
        $this->assertStringContainsString($message, $actual);
    }

    /**
     * Test that vitality check doesn't call bank confirmation callback
     */
    public function testHandleConfirmationUrlVitalityCheckDoesNotCallBankConfirmationCallback()
    {
        $dataPath = $this->GetEpsDataPath('VitalityCheckDetails.xml');
        $actual = true;
        $this->target->HandleConfirmationUrl(function ($data) use (&$actual) {
            $actual = false;
            return true;
        }, null, $dataPath, 'php://temp');
        $this->assertTrue($actual);
    }

    /**
     * Test that invalid validation callback causes exception
     */
    public function testHandleConfirmationUrlVitalityThrowsExceptionOnInvalidValidationCallback()
    {
        $dataPath = $this->GetEpsDataPath('VitalityCheckDetails.xml');
        $this->expectException(InvalidCallbackException::class);
        $this->target->HandleConfirmationUrl(function ($data) {
        }, "invalid", $dataPath, 'php://temp');
    }

    /**
     * Test that error is returned on invalid validation callback
     */
    public function testHandleConfirmationUrlVitalityReturnsErrorOnInvalidValidationCallback()
    {
        $temp = tempnam(sys_get_temp_dir(), 'SoCommunicatorTest_');
        $message = null;
        try {
            $this->target->HandleConfirmationUrl(function ($data) {
            }, "invalid", null, $temp);
        } catch (\at\externet\eps_bank_transfer\InvalidCallbackException $e) {
            $message = $e->getMessage();
        }
        $actual = file_get_contents($temp);
        XmlValidator::ValidateEpsProtocol($actual);
        $this->assertNotEmpty($message);
        $this->assertStringContainsString($message, $actual);
    }

    /**
     * Test that exception is thrown when vitality callback doesn't return true
     */
    public function testHandleConfirmationUrlVitalityThrowsExceptionWhenCallbackDoesNotReturnTrue()
    {
        $dataPath = $this->GetEpsDataPath('VitalityCheckDetails.xml');
        $this->expectException(CallbackResponseException::class);
        $this->target->HandleConfirmationUrl(function () {
        }, function ($data) {
        }, $dataPath, 'php://temp');
    }

    /**
     * Test that vitality check input is written to output stream
     */
    public function testHandleConfirmationUrlVitalityWritesInputToOutputstream()
    {
        $dataPath = $this->GetEpsDataPath('VitalityCheckDetails.xml');
        $temp = tempnam(sys_get_temp_dir(), 'SoCommunicatorTest_');
        $this->target->HandleConfirmationUrl(function () {
        }, null, $dataPath, $temp);
        $expected = file_get_contents($dataPath);
        $actual = file_get_contents($temp);
        $this->assertEquals($expected, $actual);
    }

    /**
     * Test that error is returned on invalid XML
     */
    public function testHandleConfirmationUrlReturnsErrorOnInvalidXml()
    {
        $dataPath = $this->GetEpsDataPath('BankConfirmationDetailsInvalid.xml');
        $temp = tempnam(sys_get_temp_dir(), 'SoCommunicatorTest_');
        try {
            $this->target->HandleConfirmationUrl(function () {
            }, null, $dataPath, $temp);
        } catch (\at\externet\eps_bank_transfer\XmlValidationException $e) {
            // expected
        }
        $actual = file_get_contents($temp);
        XmlValidator::ValidateEpsProtocol($actual);
        $this->assertStringContainsString('ShopResponseDetails>', $actual);
        $this->assertStringContainsString('ErrorMsg>Error occured during XML validation</', $actual);
    }

    /**
     * Test that SendRefundRequest throws XML validation exception on invalid data
     *
     * @return void
     * @throws XmlValidationException
     */
    function testSendRefundRequestThrowsValidationException()
    {
        $refundRequest = $this->getMockedRefundRequest();
        $this->http->pushResponse(200, array('Content-Type' => 'application/xml'), 'invalidData');

        $this->expectException(XmlValidationException::class);
        $this->target->SendRefundRequest($refundRequest);
    }

    /**
     * Test that SendRefundRequest sends to the correct production URL
     *
     * @return void
     * @throws XmlValidationException
     */
    function testSendRefundRequestToCorrectUrl()
    {
        $refundRequest = $this->getMockedRefundRequest();
        $this->http->pushResponse(200, array('Content-Type' => 'application/xml'), $this->GetEpsData('RefundResponseAccepted000.xml'));

        $this->target->SendRefundRequest($refundRequest);

        $info = $this->http->getLastRequestInfo();
        $this->assertEquals('https://routing.eps.or.at/appl/epsSO/refund/eps/v2_6', $info['url']);
    }

    /**
     * Test that SendRefundRequest sends to the test URL in test mode
     *
     * @return void
     * @throws XmlValidationException
     */
    function testSendRefundRequestToTestUrl()
    {
        $this->target = new SoCommunicator(true, $this->http, new HttpFactory(), new HttpFactory());
        $refundRequest = $this->getMockedRefundRequest();
        $this->http->pushResponse(200, array('Content-Type' => 'application/xml'), $this->GetEpsData('RefundResponseAccepted000.xml'));

        $this->target->SendRefundRequest($refundRequest);

        $info = $this->http->getLastRequestInfo();
        $this->assertEquals('https://routing.eps.or.at/appl/epsSO-test/refund/eps/v2_6', $info['url']);
    }

    /**
     * Test that shop response details are returned
     */
    public function testHandleConfirmationUrlReturnsShopResponse()
    {
        $dataPath = $this->GetEpsDataPath('BankConfirmationDetailsWithoutSignature.xml');
        $temp = tempnam(sys_get_temp_dir(), 'SoCommunicatorTest_');
        $this->target->HandleConfirmationUrl(function () {
            return true;
        }, null, $dataPath, $temp);

        $actual = file_get_contents($temp);
        $this->assertStringContainsString(':ShopResponseDetails', $actual);
        $this->assertStringContainsString('SessionId>13212452dea<', $actual);
        $this->assertStringContainsString('StatusCode>OK<', $actual);
        $this->assertStringContainsString('PaymentReferenceIdentifier>120000302122320812201106461<', $actual);
    }

    /**
     * Test that shop response details are returned for confirmation with signature
     */
    public function testHandleConfirmationUrlReturnsShopResponseOnConfirmationWithSignature()
    {
        $dataPath = $this->GetEpsDataPath('BankConfirmationDetailsWithSignature.xml');
        $temp = tempnam(sys_get_temp_dir(), 'SoCommunicatorTest_');
        $this->target->HandleConfirmationUrl(function () {
            return true;
        }, null, $dataPath, $temp);

        $actual = file_get_contents($temp);
        $this->assertStringContainsString(':ShopResponseDetails', $actual);
        $this->assertStringContainsString('SessionId>String<', $actual);
        $this->assertStringContainsString('StatusCode>OK<', $actual);
        $this->assertStringContainsString('PaymentReferenceIdentifier>RIAT1234567890XYZ<', $actual);
    }

    /**
     * Test that error response is returned on callback exception
     */
    public function testHandleConfirmationUrlReturnsErrorResponseOnCallbackException()
    {
        $dataPath = $this->GetEpsDataPath('BankConfirmationDetailsWithSignature.xml');
        $temp = tempnam(sys_get_temp_dir(), 'SoCommunicatorTest_');
        $catchedMessage = null;
        try {
            $this->target->HandleConfirmationUrl(function () {
                throw new \Exception('Something failed');
            }, null, $dataPath, $temp);
        } catch (\Exception $e) {
            $catchedMessage = $e->getMessage();
        }
        $this->assertEquals('Something failed', $catchedMessage);

        $actual = file_get_contents($temp);
        XmlValidator::ValidateEpsProtocol($actual);
        $this->assertStringContainsString('ShopResponseDetails>', $actual);
        $this->assertStringNotContainsString('Something failed', $actual);
        $this->assertStringContainsString('ErrorMsg>An exception of type', $actual);
    }

    /**
     * Test that exception is thrown on invalid security suffix
     */
    public function testHandleConfirmationUrlThrowsExceptionOnInvalidSecuritySuffix()
    {
        $dataPath = $this->GetEpsDataPath('BankConfirmationDetailsWithSignature.xml');
        $temp = tempnam(sys_get_temp_dir(), 'SoCommunicatorTest_');
        $this->target->ObscuritySuffixLength = 3;
        $this->target->ObscuritySeed = 'Foo';
        try {
            $this->target->HandleConfirmationUrl(function () {
            }, null, $dataPath, $temp);
        } catch (UnknownRemittanceIdentifierException $e) {
            // expected
        }

        $actual = file_get_contents($temp);
        XmlValidator::ValidateEpsProtocol($actual);
        $this->assertStringContainsString('ShopResponseDetails>', $actual);
        $this->assertStringContainsString('ErrorMsg>Unknown RemittanceIdentifier supplied', $actual);
    }

    /**
     * Test that exception is thrown on invalid security setup
     */
    public function testHandleConfirmationUrlThrowsExceptionOnInvalidSecuritySetup()
    {
        $dataPath = $this->GetEpsDataPath('BankConfirmationDetailsWithSignature.xml');
        $temp = tempnam(sys_get_temp_dir(), 'SoCommunicatorTest_');
        $this->target->ObscuritySuffixLength = 3;
        try {
            $this->target->HandleConfirmationUrl(function () {
            }, null, $dataPath, $temp);
        } catch (\UnexpectedValueException $e) {
            // expected
        }

        $actual = file_get_contents($temp);
        XmlValidator::ValidateEpsProtocol($actual);
        $this->assertStringContainsString('ShopResponseDetails>', $actual);
        $this->assertStringContainsString('ErrorMsg>An exception of type "UnexpectedValueException" occurred during handling of the confirmation url', $actual);
    }

    /**
     * Test that security hash is stripped from remittance identifier
     */
    public function testHandleConfirmationUrlStripsSecurityHashFromRemittanceIdentifier()
    {
        $original = $this->GetEpsData('BankConfirmationDetailsWithoutSignature.xml');
        $expected = 'AT1234567890XYZ';
        $data = str_replace($expected, $expected . 'Rm8', $original);
        $dataPath = tempnam(sys_get_temp_dir(), 'SoCommunicatorTest_');
        file_put_contents($dataPath, $data);

        $temp = tempnam(sys_get_temp_dir(), 'SoCommunicatorTest_');
        $this->target->ObscuritySuffixLength = 3;
        $this->target->ObscuritySeed = 'Foo';
        $bankConfirmationDetails = null;
        $this->target->HandleConfirmationUrl(function ($raw, $bc) use (&$bankConfirmationDetails) {
            $bankConfirmationDetails = $bc;
            return true;
        }, null, $dataPath, $temp);

        $this->assertSame($expected, $bankConfirmationDetails->GetRemittanceIdentifier());
    }

    /**
     * Test that vitality check is logged
     */
    public function testWriteLog()
    {
        $dataPath = $this->GetEpsDataPath('VitalityCheckDetails.xml');
        $message = null;
        $this->target->LogCallback = function ($m) use (&$message) {
            $message = $m;
        };
        $this->target->HandleConfirmationUrl(function () {
        }, function ($data) {
            return true;
        }, $dataPath, 'php://temp');
        $this->assertEquals('Vitality Check', $message);
    }

    /**
     * Test that successful payment order is logged
     */
    public function testWriteLogSendTransferInitiatorDetailsSuccess()
    {
        $transferInitiatorDetails = $this->getMockedTransferInitiatorDetails();
        $this->http->pushResponse(200, array('Content-Type' => 'application/xml'), $this->GetEpsData('BankResponseDetails000.xml'));
        $message = null;
        $this->target->LogCallback = function ($m) use (&$message) {
            $message = $m;
        };

        $this->target->SendTransferInitiatorDetails($transferInitiatorDetails);

        $this->assertEquals('SUCCESS: Send payment order', $message);
    }

    /**
     * Test that failed payment order is logged
     */
    public function testWriteLogSendTransferInitiatorDetailsFailed()
    {
        $transferInitiatorDetails = $this->getMockedTransferInitiatorDetails();
        $this->http->pushResponse(400, array('Content-Type' => 'application/xml'), 'error');
        $message = null;
        $this->target->LogCallback = function ($m) use (&$message) {
            $message = $m;
        };

        try {
            $this->target->SendTransferInitiatorDetails($transferInitiatorDetails);
        } catch (\RuntimeException $e) {
        }

        $this->assertEquals('FAILED: Send payment order', $message);
    }

    // HELPER FUNCTIONS

    /**
     * Creates a mocked TransferInitiatorDetails instance for testing
     *
     * @return TransferInitiatorDetails
     */
    private function getMockedTransferInitiatorDetails()
    {
        $simpleXml = $this->getMockBuilder(EpsXmlElement::class)
            ->setConstructorArgs(array('<xml/>'))
            ->getMock();
        $simpleXml->expects($this->any())
            ->method('asXML')
            ->will($this->returnValue('<xml>Mocked Data'));

        $transferInitiatorDetails = $this->getMockBuilder(TransferInitiatorDetails::class)
            ->disableOriginalConstructor()
            ->getMock();
        $transferInitiatorDetails->expects($this->any())
            ->method('GetSimpleXml')
            ->will($this->returnValue($simpleXml));

        $transferInitiatorDetails->RemittanceIdentifier = 'orderid';

        return $transferInitiatorDetails;
    }

    /**
     * Creates a mocked RefundRequest instance for testing
     *
     * @return EpsRefundRequest
     */
    private function getMockedRefundRequest()
    {
        $simpleXml = $this->getMockBuilder(EpsXmlElement::class)
            ->setConstructorArgs(array('<xml/>'))
            ->getMock();
        $simpleXml->expects($this->any())
            ->method('asXML')
            ->will($this->returnValue('<xml>Mocked Refund Data'));

        $refundRequest = $this->getMockBuilder(EpsRefundRequest::class)
            ->disableOriginalConstructor()
            ->getMock();
        $refundRequest->expects($this->any())
            ->method('GetSimpleXml')
            ->will($this->returnValue($simpleXml));

        $refundRequest->CreDtTm = '2025-02-10T15:30:00';
        $refundRequest->TransactionId = '1234567890';
        $refundRequest->MerchantIBAN = 'AT611904300234573201';
        $refundRequest->Amount = 100.50;
        $refundRequest->AmountCurrencyIdentifier = 'EUR';
        $refundRequest->UserId = 'TestUserId';
        $refundRequest->Pin = 'TestPin';
        $refundRequest->RefundReference = 'Duplicate transaction';

        return $refundRequest;
    }
}
<?php
declare(strict_types=1);

namespace Externet\EpsBankTransfer\Tests\Api\V26;

use Externet\EpsBankTransfer\Api\AbstractSoCommunicator;
use Externet\EpsBankTransfer\Exceptions\XmlValidationException;
use Externet\EpsBankTransfer\Requests\InitiateTransferRequest;
use Externet\EpsBankTransfer\Requests\Parts\PaymentFlowUrls;
use Externet\EpsBankTransfer\Tests\Helper\SoV26CommunicatorTestTrait;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use UnexpectedValueException;

class InitiateTransferRequestTest extends TestCase
{
    use SoV26CommunicatorTestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpCommunicator();
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

    public function testSendTransferInitiatorDetailsThrowsValidationException(): void
    {
        $this->expectException(XmlValidationException::class);
        $this->mockResponse(200, 'invalidData');
        $this->target->initiateTransferRequest($this->getMockedTransferInitiatorDetails());
    }

    public function testSendTransferInitiatorDetailsToCorrectUrl(): void
    {
        $this->mockResponse(200, $this->loadFixture('V26/BankResponseDetails004.xml'));
        $this->target->initiateTransferRequest($this->getMockedTransferInitiatorDetails());
        $this->assertEquals(
            'https://routing.eps.or.at/appl/epsSO/transinit/eps/v2_6',
            $this->http->getLastRequestInfo()['url']
        );
    }

    public function testSendTransferInitiatorDetailsToTestUrl(): void
    {
        $this->setUpCommunicator(AbstractSoCommunicator::TEST_MODE_URL);
        $this->mockResponse(200, $this->loadFixture('V26/BankResponseDetails004.xml'));
        $this->target->initiateTransferRequest($this->getMockedTransferInitiatorDetails());
        $this->assertEquals(
            'https://routing-test.eps.or.at/appl/epsSO/transinit/eps/v2_6',
            $this->http->getLastRequestInfo()['url']
        );
    }

    public function testSendTransferInitiatorDetailsThrowsExceptionOn404(): void
    {
        $this->expectException(RuntimeException::class);
        $this->mockResponse(404, 'Not found');
        $this->target->initiateTransferRequest($this->getMockedTransferInitiatorDetails());
    }

    public function testSendTransferInitiatorDetailsWithPreselectedBank(): void
    {
        $url = 'https://routing.eps.or.at/appl/epsSO/transinit/eps/v2_6/23ea3d14-278c-4e81-a021-d7b77492b611';
        $this->mockResponse(200, $this->loadFixture('V26/BankResponseDetails000.xml'));
        $this->target->initiateTransferRequest($this->getMockedTransferInitiatorDetails(), $url);
        $this->assertEquals($url, $this->http->getLastRequestInfo()['url']);
    }

    public function testSendTransferInitiatorDetailsWithSecurityThrowsExceptionOnEmptySalt(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->target->setObscuritySuffixLength(8);
        $this->mockResponse(200, $this->loadFixture('V26/BankResponseDetails000.xml'));
        $this->target->initiateTransferRequest($this->getMockedTransferInitiatorDetails());
    }

    public function testSendTransferInitiatorDetailsWithSecurityAppendsHash(): void
    {
        $this->target->setObscuritySuffixLength(8);
        $this->target->setObscuritySeed('Some seed');
        $this->mockResponse(200, $this->loadFixture('V26/BankResponseDetails000.xml'));

        $t = new PaymentFlowUrls('a', 'b', 'c');
        $transferInitiatorDetails = new InitiateTransferRequest('a', 'b', 'c', 'd', 'e', 'f', 0, $t);
        $transferInitiatorDetails->remittanceIdentifier = 'Order1';

        $url = 'https://routing.eps.or.at/appl/epsSO/transinit/eps/v2_6/someid';
        $this->target->initiateTransferRequest($transferInitiatorDetails, $url);

        $body = $this->http->getLastRequestInfo()['body'];
        $this->assertStringContainsString('Order1', $body);
    }

    public function testSendTransferInitiatorDetailsWithOverriddenBaseUrl(): void
    {
        $this->target->setBaseUrl('http://example.com');
        $this->mockResponse(200, $this->loadFixture('V26/BankResponseDetails004.xml'));
        $this->target->initiateTransferRequest($this->getMockedTransferInitiatorDetails());
        $this->assertEquals(
            'http://example.com/transinit/eps/v2_6',
            $this->http->getLastRequestInfo()['url']
        );
    }

    public function testSendTransferInitiatorDetailsRequestContainsMandatoryFields(): void
    {
        $this->mockResponse(200, $this->loadFixture('V26/BankResponseDetails004.xml'));
        $this->target->initiateTransferRequest($this->getMockedTransferInitiatorDetails());

        $body = $this->http->getLastRequestInfo()['body'];
        $this->assertStringContainsString('orderid', $body); // remittanceIdentifier
        $this->assertStringContainsString('AT611904300234573201', $body); // IBAN
        $this->assertStringContainsString('120.5', $body); // amount
        $this->assertStringContainsString('TestShop', $body); // userId 
        $this->assertStringContainsString('Test Company GmbH', $body); // beneficiaryName
        $this->assertStringContainsString('TESTBANKXXX', $body); // bankId
        $this->assertStringContainsString('REF123456789', $body); // referenceId
        $this->assertStringContainsString('https://example.com/confirmation', $body); // confirmationUrl
        $this->assertStringContainsString('https://example.com/success', $body); // successUrl
        $this->assertStringContainsString('https://example.com/failure', $body); // errorUrl
    }

    public function testSendTransferInitiatorDetailsParsesResponse(): void
    {
        $this->mockResponse(200, $this->loadFixture('V26/BankResponseDetails004.xml'));
        $response = $this->target->initiateTransferRequest($this->getMockedTransferInitiatorDetails());

        $this->assertNotNull($response);
        $this->assertEquals('004', $response->getBankResponseDetails()->getErrorDetails()->getErrorCode());
        $this->assertEquals('merchant not found!', $response->getBankResponseDetails()->getErrorDetails()->getErrorMsg());
    }

    public function testSendTransferInitiatorDetailsThrowsExceptionOnWrongContentType(): void
    {
        $this->mockResponse(200, '<xml>valid but wrong header</xml>', ['Content-Type' => 'text/plain']);
        $this->expectException(XmlValidationException::class);
        $this->target->initiateTransferRequest($this->getMockedTransferInitiatorDetails());
    }


    /**
     * @dataProvider provideValidAmounts
     */
    public function testSendTransferInitiatorDetailsFormatsAmountCorrectly($inputAmount, string $expectedInXml): void
    {
        $this->mockResponse(200, $this->loadFixture('V26/BankResponseDetails004.xml'));

        $t = new PaymentFlowUrls(
            'https://example.com/confirmation',
            'https://example.com/success',
            'https://example.com/failure'
        );

        $ti = new InitiateTransferRequest(
            'TestShop',
            'secret123',
            'TESTBANKXXX',
            'Test Company GmbH',
            'AT611904300234573201',
            'REF123456789',
            $inputAmount,
            $t
        );
        $ti->remittanceIdentifier = 'orderid';

        $this->target->initiateTransferRequest($ti);

        $body = $this->http->getLastRequestInfo()['body'];

        $this->assertStringContainsString($expectedInXml, $body, "Expected amount $expectedInXml in XML body");
    }

    public static function provideValidAmounts(): array
    {
        return [
            'zero' => [0, '0.00'],
            'one cent' => [1, '0.01'],
            'twelve cents' => [12, '0.12'],
            'one euro' => [100, '1.00'],
            'ten euros' => [1000, '10.00'],
            '123 euro 45 cent' => [12345, '123.45'],
            'string as int' => ['12345', '123.45'],
            'max cents' => [99, '0.99'],
            'max amount' => [999999999, '9999999.99'],
        ];
    }

    /**
     * @dataProvider provideInvalidAmounts
     */
    public function testSendTransferInitiatorDetailsThrowsOnInvalidAmount($invalidAmount): void
    {
        $t = new PaymentFlowUrls('a', 'b', 'c');

        $this->expectException(\InvalidArgumentException::class);

        new InitiateTransferRequest(
            'TestShop',
            'secret123',
            'TESTBANKXXX',
            'Test Company GmbH',
            'AT611904300234573201',
            'REF123456789',
            $invalidAmount,
            $t
        );
    }

    public static function provideInvalidAmounts(): array
    {
        return [
            'float'         => [123.45],
            'string float'  => ['123.45'],
            'null'          => [null],
            'array'         => [[100]],
            'object'        => [(object)['val' => 100]],
        ];
    }

}

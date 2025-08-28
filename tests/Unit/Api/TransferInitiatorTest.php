<?php
declare(strict_types=1);

namespace Externet\EpsBankTransfer\Tests\Api;

use Externet\EpsBankTransfer\Requests\InitiateTransferRequest;
use Externet\EpsBankTransfer\Requests\Parts\PaymentFlowUrls;
use Externet\EpsBankTransfer\Exceptions\XmlValidationException;
use Externet\EpsBankTransfer\Tests\Helper\SoV26CommunicatorTestTrait;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use UnexpectedValueException;

class TransferInitiatorTest extends TestCase
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
        $this->target->sendTransferInitiatorDetails($this->getMockedTransferInitiatorDetails());
    }

    public function testSendTransferInitiatorDetailsToCorrectUrl(): void
    {
        $this->mockResponse(200, $this->loadFixture('BankResponseDetails004.xml'));
        $this->target->sendTransferInitiatorDetails($this->getMockedTransferInitiatorDetails());
        $this->assertEquals(
            'https://routing.eps.or.at/appl/epsSO/transinit/eps/v2_6',
            $this->http->getLastRequestInfo()['url']
        );
    }

    public function testSendTransferInitiatorDetailsToTestUrl(): void
    {
        $this->setUpCommunicator(\Externet\EpsBankTransfer\Api\SoV26Communicator::TEST_MODE_URL);
        $this->mockResponse(200, $this->loadFixture('BankResponseDetails004.xml'));
        $this->target->sendTransferInitiatorDetails($this->getMockedTransferInitiatorDetails());
        $this->assertEquals(
            'https://routing-test.eps.or.at/appl/epsSO/transinit/eps/v2_6',
            $this->http->getLastRequestInfo()['url']
        );
    }

    public function testSendTransferInitiatorDetailsThrowsExceptionOn404(): void
    {
        $this->expectException(RuntimeException::class);
        $this->mockResponse(404, 'Not found');
        $this->target->sendTransferInitiatorDetails($this->getMockedTransferInitiatorDetails());
    }

    public function testSendTransferInitiatorDetailsWithPreselectedBank(): void
    {
        $url = 'https://routing.eps.or.at/appl/epsSO/transinit/eps/v2_6/23ea3d14-278c-4e81-a021-d7b77492b611';
        $this->mockResponse(200, $this->loadFixture('BankResponseDetails000.xml'));
        $this->target->sendTransferInitiatorDetails($this->getMockedTransferInitiatorDetails(), $url);
        $this->assertEquals($url, $this->http->getLastRequestInfo()['url']);
    }

    public function testSendTransferInitiatorDetailsWithSecurityThrowsExceptionOnEmptySalt(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->target->setObscuritySuffixLength(8);
        $this->mockResponse(200, $this->loadFixture('BankResponseDetails000.xml'));
        $this->target->sendTransferInitiatorDetails($this->getMockedTransferInitiatorDetails());
    }

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
}

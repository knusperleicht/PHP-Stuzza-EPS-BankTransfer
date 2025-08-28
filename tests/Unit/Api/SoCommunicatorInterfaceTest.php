<?php
declare(strict_types=1);

namespace Externet\EpsBankTransfer\Tests\Api;

use Externet\EpsBankTransfer\Api\SoCommunicatorInterface;
use Externet\EpsBankTransfer\Api\SoV26Communicator;
use Externet\EpsBankTransfer\Api\SoV27Communicator;
use Externet\EpsBankTransfer\Requests\RefundRequest;
use Externet\EpsBankTransfer\Tests\Helper\Psr18TestHttp;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class SoCommunicatorInterfaceTest extends TestCase
{
    /**
     * @return array<string,array{0:SoCommunicatorInterface,1:Psr18TestHttp}>
     */
    public function communicatorProvider(): array
    {
        $httpClient   = new Psr18TestHttp();
        $psr17Factory = new Psr17Factory();
        $logger       = new NullLogger();

        return [
            'V26' => [
                new SoV26Communicator(
                    $httpClient,
                    $psr17Factory,
                    $psr17Factory,
                    SoV26Communicator::TEST_MODE_URL,
                    $logger
                ),
                $httpClient
            ],
            'V27' => [
                new SoV27Communicator(
                    $httpClient,
                    $psr17Factory,
                    $psr17Factory,
                    SoV27Communicator::TEST_MODE_URL,
                    $logger
                ),
                $httpClient
            ],
        ];
    }

    /**
     * @dataProvider communicatorProvider
     */
    public function testImplementsInterface(SoCommunicatorInterface $communicator): void
    {
        $this->assertInstanceOf(SoCommunicatorInterface::class, $communicator);
    }

    /**
     * @dataProvider communicatorProvider
     */
    public function testGetBanksReturnsXml(SoCommunicatorInterface $communicator, Psr18TestHttp $httpClient): void
    {
        $httpClient->pushResponse(200, ['Content-Type' => 'application/xml'], '<banks/>');
        $xml = $communicator->getBanks(false);

        $this->assertIsString($xml);
        $this->assertStringContainsString('<banks', $xml);
    }

    /**
     * @dataProvider communicatorProvider
     */
    public function testSendRefundRequestSerializesAndPosts(SoCommunicatorInterface $communicator, Psr18TestHttp $httpClient): void
    {
        $responseXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<EpsRefundResponse xmlns="http://www.stuzza.at/namespaces/eps/refund/2018/09">
  <StatusCode>OK</StatusCode>
</EpsRefundResponse>
XML;

        $httpClient->pushResponse(200, ['Content-Type' => 'application/xml'], $responseXml);

        $refundRequest = new RefundRequest(
            date('c'),
            'session123',
            'AT611904300234573201',
            "100",
            'EUR',
            'USERID',
            'PIN'
        );
        $result = $communicator->sendRefundRequest($refundRequest);

        $this->assertIsObject($result);
        $this->assertEquals('OK', $result->getStatusCode());;
    }

    /**
     * @dataProvider communicatorProvider
     */
    public function testSetBaseUrlChangesRequestTarget(SoCommunicatorInterface $communicator, Psr18TestHttp $httpClient): void
    {
        $communicator->setBaseUrl('https://example.com/eps');
        $httpClient->pushResponse(200, ['Content-Type' => 'application/xml'], '<banks/>');

        $communicator->getBanks(false);
        $last = $httpClient->getLastRequestInfo();

        $this->assertStringStartsWith('https://example.com/eps', $last['url']);
    }
}

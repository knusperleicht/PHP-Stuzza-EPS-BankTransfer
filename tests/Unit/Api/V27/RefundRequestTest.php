<?php
declare(strict_types=1);

namespace Externet\EpsBankTransfer\Tests\Api\V27;

use Externet\EpsBankTransfer\Api\AbstractSoCommunicator;
use Externet\EpsBankTransfer\Exceptions\XmlValidationException;
use Externet\EpsBankTransfer\Requests\RefundRequest;
use Externet\EpsBankTransfer\Tests\Helper\SoV27CommunicatorTestTrait;
use PHPUnit\Framework\TestCase;

class RefundRequestTest extends TestCase
{
    use SoV27CommunicatorTestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpCommunicator();
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

    public function testSendRefundRequestThrowsValidationException(): void
    {
        $refundRequest = $this->getMockedRefundRequest();
        $this->mockResponse(200, 'invalidData');
        $this->expectException(XmlValidationException::class);
        $this->target->sendRefundRequest($refundRequest);
    }

    /**
     * @dataProvider provideRefundUrls
     */
    public function testSendRefundRequestToCorrectUrl(string $modeUrl, string $expectedUrl): void
    {
        $this->setUpCommunicator($modeUrl);
        $refundRequest = $this->getMockedRefundRequest();
        $this->mockResponse(200, $this->loadFixture('V26/RefundResponseAccepted000.xml'));
        $this->target->sendRefundRequest($refundRequest);
        $this->assertEquals($expectedUrl, $this->http->getLastRequestInfo()['url']);
    }

    public static function provideRefundUrls(): array
    {
        return [
            'live' => [AbstractSoCommunicator::LIVE_MODE_URL, 'https://routing.eps.or.at/appl/epsSO/refund/eps/v2_6'],
            'test' => [AbstractSoCommunicator::TEST_MODE_URL, 'https://routing-test.eps.or.at/appl/epsSO/refund/eps/v2_6'],
        ];
    }

    public function testSendRefundRequestThrowsOn404(): void
    {
        $refundRequest = $this->getMockedRefundRequest();
        $this->mockResponse(404, 'Not found', ['Content-Type' => 'text/plain']);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('POST https://routing.eps.or.at/appl/epsSO/refund/eps/v2_6 failed with HTTP 404');
        $this->target->sendRefundRequest($refundRequest);
    }

    public function testSendRefundRequestParsesAcceptedResponse(): void
    {
        $refundRequest = $this->getMockedRefundRequest();
        $this->mockResponse(200, $this->loadFixture('V26/RefundResponseAccepted000.xml'));

        $result = $this->target->sendRefundRequest($refundRequest);

        $this->assertNotNull($result);
        $this->assertEquals('000', $result->getStatusCode());
    }

    public function testSendRefundRequestWithOverriddenBaseUrl(): void
    {
        $this->target->setBaseUrl('http://example.com');
        $refundRequest = $this->getMockedRefundRequest();
        $this->mockResponse(200, $this->loadFixture('V26/RefundResponseAccepted000.xml'));

        $this->target->sendRefundRequest($refundRequest);
        $this->assertEquals(
            'http://example.com/refund/eps/v2_6',
            $this->http->getLastRequestInfo()['url']
        );
    }
}
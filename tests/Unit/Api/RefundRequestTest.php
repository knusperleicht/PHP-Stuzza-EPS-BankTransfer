<?php
declare(strict_types=1);

namespace Psa\EpsBankTransfer\Tests\Api;

use Psa\EpsBankTransfer\Api\SoCommunicator;
use Psa\EpsBankTransfer\Domain\RefundResponse;
use Psa\EpsBankTransfer\Exceptions\XmlValidationException;
use Psa\EpsBankTransfer\Requests\RefundRequest;
use Psa\EpsBankTransfer\Tests\Helper\SoCommunicatorTestTrait;
use PHPUnit\Framework\TestCase;

class RefundRequestTest extends TestCase
{
    use SoCommunicatorTestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpCommunicator();
    }

    private function getMockedRefundRequest(): RefundRequest
    {
        return new RefundRequest(
            '2018-09-25T08:09:53.454+02:00',
            'epsJMG15K752',
            'AT175700054011014943',
            3,
            'EUR',
            'HYPTAT22XXX_143921',
            'fluxkompensator!',
            'REFUND-123456789'
        );
    }

    /* ============================================================
     * Version dependent tests 
     * ============================================================
     */

    public function testSendRefundRequestV26(): void
    {
        $refundRequest = $this->getMockedRefundRequest();
        $this->mockResponse(200, $this->loadFixture('V26/RefundResponseAccepted000.xml'));

        $result = $this->target->sendRefundRequest($refundRequest, '2.6');

        $expected = new RefundResponse(
            '000',
            'Keine Fehler - dt accepted',
        );

        $this->assertEquals($expected, $result);

        $lastRequestInfo = $this->http->getLastRequestInfo();
        $lastUrl = $lastRequestInfo['url'];
        $lastBody = $lastRequestInfo['body'];

        $this->assertStringContainsString('/v2_6', $lastUrl);
        $this->assertStringContainsString('2018-09-25T08:09:53.454+02:00', $lastBody);
        $this->assertStringContainsString('epsJMG15K752', $lastBody);
        $this->assertStringContainsString('AT175700054011014943', $lastBody);
        $this->assertStringContainsString('0.03', $lastBody);
        $this->assertStringContainsString('EUR', $lastBody);
        $this->assertStringContainsString('HYPTAT22XXX_143921', $lastBody);
        $this->assertStringContainsString('DB189543CF68F36893465F5844092B26C332B95A97F1AF6A1B1392CCC605BC40', $lastBody);
        $this->assertStringContainsString('REFUND-123456789', $lastBody);
    }

    public function testSendRefundRequestV27Throws(): void
    {
        $refundRequest = $this->getMockedRefundRequest();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Not implemented yet - waiting for XSD 2.7');

        $this->target->sendRefundRequest($refundRequest, '2.7');
    }

    public function testSendRefundRequestThrowsOnUnsupportedVersion(): void
    {
        $refundRequest = $this->getMockedRefundRequest();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported version');

        $this->target->sendRefundRequest($refundRequest, 'foo');
    }

    /* ============================================================
     * Error handling
     * ============================================================
     */

    public function testSendRefundRequestThrowsValidationException(): void
    {
        $refundRequest = $this->getMockedRefundRequest();
        $this->mockResponse(200, 'invalidData');

        $this->expectException(XmlValidationException::class);
        $this->target->sendRefundRequest($refundRequest, '2.6');
    }

    public function testSendRefundRequestThrowsOn404(): void
    {
        $refundRequest = $this->getMockedRefundRequest();
        $this->mockResponse(404, 'Not found', ['Content-Type' => 'text/plain']);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('POST https://routing.eps.or.at/appl/epsSO/refund/eps/v2_6 failed with HTTP 404');
        $this->target->sendRefundRequest($refundRequest, '2.6');
    }

    /* ============================================================
     * URL and parsing tests
     * ============================================================
     */

    /**
     * @dataProvider provideRefundUrls
     */
    public function testSendRefundRequestToCorrectUrl(string $modeUrl, string $expectedUrl): void
    {
        $this->setUpCommunicator($modeUrl);
        $refundRequest = $this->getMockedRefundRequest();
        $this->mockResponse(200, $this->loadFixture('V26/RefundResponseAccepted000.xml'));

        $this->target->sendRefundRequest($refundRequest, '2.6');

        $this->assertEquals($expectedUrl, $this->http->getLastRequestInfo()['url']);
    }

    public static function provideRefundUrls(): array
    {
        return [
            'live' => [SoCommunicator::LIVE_MODE_URL, 'https://routing.eps.or.at/appl/epsSO/refund/eps/v2_6'],
            'test' => [SoCommunicator::TEST_MODE_URL, 'https://routing-test.eps.or.at/appl/epsSO/refund/eps/v2_6'],
        ];
    }

    public function testSendRefundRequestWithOverriddenBaseUrl(): void
    {
        $this->target->setBaseUrl('http://example.com');
        $refundRequest = $this->getMockedRefundRequest();
        $this->mockResponse(200, $this->loadFixture('V26/RefundResponseAccepted000.xml'));

        $this->target->sendRefundRequest($refundRequest, '2.6');
        $this->assertEquals(
            'http://example.com/refund/eps/v2_6',
            $this->http->getLastRequestInfo()['url']
        );
    }
}
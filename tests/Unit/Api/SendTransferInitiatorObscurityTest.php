<?php
declare(strict_types=1);

namespace Knusperleicht\EpsBankTransfer\Tests\Api;

use PHPUnit\Framework\TestCase;
use Knusperleicht\EpsBankTransfer\Requests\TransferInitiatorDetails;
use Knusperleicht\EpsBankTransfer\Requests\Parts\PaymentFlowUrls;
use Knusperleicht\EpsBankTransfer\Requests\Parts\ObscurityConfig;
use Knusperleicht\EpsBankTransfer\Tests\Helper\SoCommunicatorTestTrait;

class SendTransferInitiatorObscurityTest extends TestCase
{
    use SoCommunicatorTestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpCommunicator();
    }

    private function baseDetails(): TransferInitiatorDetails
    {
        $urls = new PaymentFlowUrls('https://c', 'https://o', 'https://n');
        $ti = new TransferInitiatorDetails('U', 'S', 'B', 'Benef', 'AT528900000001100471', 'REF', 100, $urls);
        return $ti;
    }

    /**
     * @dataProvider provideVersions
     */
    public function testStructuredTooLongWithSuffixThrows(string $version, string $pathPrefix): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->mockResponse(200, $this->loadFixture($pathPrefix . '/BankResponseDetails000.xml'));

        $ti = $this->baseDetails();
        // Base length 35 exactly, suffix 1 would exceed 35
        $ti->setRemittanceIdentifier(str_repeat('A', 35));
        $ti->setObscurityConfig(new ObscurityConfig(1, 'seed'));

        $this->target->sendTransferInitiatorDetails($ti, $version);
    }

    /**
     * @dataProvider provideVersions
     */
    public function testUnstructuredTooLongWithSuffixThrows(string $version, string $pathPrefix): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->mockResponse(200, $this->loadFixture($pathPrefix . '/BankResponseDetails000.xml'));

        $ti = $this->baseDetails();
        $ti->setUnstructuredRemittanceIdentifier(str_repeat('B', 140));
        $ti->setObscurityConfig(new ObscurityConfig(1, 'seed'));

        $this->target->sendTransferInitiatorDetails($ti, $version);
    }

    /**
     * @dataProvider provideVersions
     */
    public function testStructuredAtLimitWithZeroSuffixOk(string $version, string $pathPrefix): void
    {
        $this->mockResponse(200, $this->loadFixture($pathPrefix . '/BankResponseDetails000.xml'));
        $ti = $this->baseDetails();
        $ti->setRemittanceIdentifier(str_repeat('C', 35));
        $ti->setObscurityConfig(new ObscurityConfig(0, null));
        $this->target->sendTransferInitiatorDetails($ti, $version);
        $body = $this->http->getLastRequestInfo()['body'];
        $this->assertStringContainsString(str_repeat('C', 35), $body);
    }

    /**
     * @dataProvider provideVersions
     */
    public function testUnstructuredAtLimitWithZeroSuffixOk(string $version, string $pathPrefix): void
    {
        $this->mockResponse(200, $this->loadFixture($pathPrefix . '/BankResponseDetails000.xml'));
        $ti = $this->baseDetails();
        $ti->setUnstructuredRemittanceIdentifier(str_repeat('D', 140));
        $ti->setObscurityConfig(new ObscurityConfig(0, null));
        $this->target->sendTransferInitiatorDetails($ti, $version);
        $body = $this->http->getLastRequestInfo()['body'];
        $this->assertStringContainsString(str_repeat('D', 140), $body);
    }

    public function provideVersions(): array
    {
        return [
            'V2.6' => ['2.6', 'V26'],
            'V2.7' => ['2.7', 'V27']
        ];
    }
}
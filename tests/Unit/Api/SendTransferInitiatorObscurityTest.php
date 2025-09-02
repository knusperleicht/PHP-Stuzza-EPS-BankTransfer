<?php
declare(strict_types=1);

namespace Psa\EpsBankTransfer\Tests\Api;

use PHPUnit\Framework\TestCase;
use Psa\EpsBankTransfer\Requests\TransferInitiatorDetails;
use Psa\EpsBankTransfer\Requests\Parts\PaymentFlowUrls;
use Psa\EpsBankTransfer\Requests\Parts\ObscurityConfig;
use Psa\EpsBankTransfer\Tests\Helper\SoCommunicatorTestTrait;

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
        $urls = new PaymentFlowUrls('https://c','https://o','https://n');
        $ti = new TransferInitiatorDetails('U','S','B','Benef','AT528900000001100471','REF',100,$urls);
        return $ti;
    }

    public function testStructuredTooLongWithSuffixThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->mockResponse(200, $this->loadFixture('V26/BankResponseDetails000.xml'));

        $ti = $this->baseDetails();
        // Base length 35 exactly, suffix 1 would exceed 35
        $ti->setRemittanceIdentifier(str_repeat('A', 35));
        $ti->setObscurityConfig(new ObscurityConfig(1, 'seed'));

        $this->target->sendTransferInitiatorDetails($ti, '2.6');
    }

    public function testUnstructuredTooLongWithSuffixThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->mockResponse(200, $this->loadFixture('V26/BankResponseDetails000.xml'));

        $ti = $this->baseDetails();
        $ti->setUnstructuredRemittanceIdentifier(str_repeat('B', 140));
        $ti->setObscurityConfig(new ObscurityConfig(1, 'seed'));

        $this->target->sendTransferInitiatorDetails($ti, '2.6');
    }

    public function testStructuredAtLimitWithZeroSuffixOk(): void
    {
        $this->mockResponse(200, $this->loadFixture('V26/BankResponseDetails000.xml'));
        $ti = $this->baseDetails();
        $ti->setRemittanceIdentifier(str_repeat('C', 35));
        $ti->setObscurityConfig(new ObscurityConfig(0, null));
        $this->target->sendTransferInitiatorDetails($ti, '2.6');
        $body = $this->http->getLastRequestInfo()['body'];
        $this->assertStringContainsString(str_repeat('C', 35), $body);
    }

    public function testUnstructuredAtLimitWithZeroSuffixOk(): void
    {
        $this->mockResponse(200, $this->loadFixture('V26/BankResponseDetails000.xml'));
        $ti = $this->baseDetails();
        $ti->setUnstructuredRemittanceIdentifier(str_repeat('D', 140));
        $ti->setObscurityConfig(new ObscurityConfig(0, null));
        $this->target->sendTransferInitiatorDetails($ti, '2.6');
        $body = $this->http->getLastRequestInfo()['body'];
        $this->assertStringContainsString(str_repeat('D', 140), $body);
    }
}

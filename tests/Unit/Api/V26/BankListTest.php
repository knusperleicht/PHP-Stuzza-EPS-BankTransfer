<?php
declare(strict_types=1);

namespace Externet\EpsBankTransfer\Tests\Api\V26;

use Externet\EpsBankTransfer\Api\V26\SoV26Communicator;
use Externet\EpsBankTransfer\Exceptions\XmlValidationException;
use Externet\EpsBankTransfer\Generated\BankList\EpsSOBankListProtocol;
use Externet\EpsBankTransfer\Tests\Helper\SoV26CommunicatorTestTrait;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class BankListTest extends TestCase
{
    use SoV26CommunicatorTestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpCommunicator();
    }

    public function testGetBanksSuccess(): void
    {
        $this->mockResponse(200, $this->loadFixture('BankListSample.xml'));
        $banks = $this->target->getBanks();
        $this->assertInstanceOf(EpsSOBankListProtocol::class, $banks);
    }

    /**
     * @dataProvider provideBankUrls
     */
    public function testGetBanksCallsCorrectUrl(string $modeUrl, string $expectedUrl): void
    {
        $this->setUpCommunicator($modeUrl);
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

    public function testGetBankListReadError(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('GET https://routing.eps.or.at/appl/epsSO/data/haendler/v2_6 failed with HTTP 404');
        $this->mockResponse(404, 'Not found', ['Content-Type' => 'text/plain']);
        $this->target->getBanks();
    }

    public function testOverrideDefaultBaseUrl(): void
    {
        $this->target->setBaseUrl('http://example.com');
        $this->mockResponse(200, $this->loadFixture('BankListSample.xml'));
        $this->target->getBanks();
        $this->assertEquals('http://example.com/data/haendler/v2_6', $this->http->getLastRequestInfo()['url']);
    }

    public function testGetBanksThrowsValidationExceptionOnInvalidXml(): void
    {
        $this->mockResponse(200, 'invalidData');
        $this->expectException(XmlValidationException::class);
        $this->target->getBanks();
    }

    public function testGetBanksHandlesEmptyList(): void
    {
        $this->mockResponse(200, $this->loadFixture('BankListEmpty.xml'));
        $banks = $this->target->getBanks();
        $this->assertInstanceOf(EpsSOBankListProtocol::class, $banks);
        $this->assertCount(0, $banks->getBank());
    }

    public function testGetBanksParsesBankFields(): void
    {
        $this->mockResponse(200, $this->loadFixture('BankListSample.xml'));
        $banks = $this->target->getBanks();
        $first = $banks->getBank()[0];
        $this->assertEquals('TESTBANKXXX', $first->getBic());
        $this->assertEquals('Testbank', $first->getBezeichnung());
        $this->assertEquals('AT', $first->getLand());
        $this->assertEquals('https://routing.eps.or.at/appl/epsSO/transinit/eps/v2_6/23ea3d14-278c-4e81-a021-d7b77492b611', $first->getEpsUrl());
    }
}

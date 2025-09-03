<?php
declare(strict_types=1);

namespace Knusperleicht\EpsBankTransfer\Tests\Api;

use Knusperleicht\EpsBankTransfer\Api\SoCommunicator;
use Knusperleicht\EpsBankTransfer\Domain\Bank;
use Knusperleicht\EpsBankTransfer\Domain\BankList;
use Knusperleicht\EpsBankTransfer\Exceptions\BankListException;
use Knusperleicht\EpsBankTransfer\Exceptions\XmlValidationException;
use Knusperleicht\EpsBankTransfer\Tests\Helper\SoCommunicatorTestTrait;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class BankListTest extends TestCase
{
    use SoCommunicatorTestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpCommunicator();
    }

    /* ============================================================
     * Data Providers
     * ============================================================
     */
    public static function provideSoUrls(): array
    {
        return [
            'live' => [SoCommunicator::LIVE_MODE_URL, 'https://routing.eps.or.at/appl/epsSO/data/haendler/v2_6'],
            'test' => [SoCommunicator::TEST_MODE_URL, 'https://routing-test.eps.or.at/appl/epsSO/data/haendler/v2_6'],
        ];
    }

    /* ============================================================
     * Version dependent tests
     * ============================================================
     */

    public function testGetBanksV26(): void
    {
        $this->mockResponse(200, $this->loadFixture('BankListSample.xml'));

        $banks = $this->target->getBanks('2.6');

        $this->assertInstanceOf(BankList::class, $banks);
        $this->assertEquals([
            new Bank(
                'TESTBANKXXX',
                'Testbank',
                'https://routing.eps.or.at/appl/epsSO/transinit/eps/v2_6/23ea3d14-278c-4e81-a021-d7b77492b611',
                'AT',
                ['EPG'],
                null,
                false
            ),
        ], $banks->getBanks());

        $lastUrl = $this->http->getLastRequestInfo()['url'];
        $this->assertStringContainsString('/v2_6', $lastUrl);
    }

    public function testGetBanksHandlesApp2AppFieldGracefully(): void
    {
        $this->mockResponse(200, $this->loadFixture('BankListWithApp2AppField.xml'));

        $list = $this->target->getBanks('2.6');
        $this->assertInstanceOf(BankList::class, $list);

        $this->assertEquals([
            new Bank(
                'BKAUATWWXXX',
                'Bank Austria',
                'https://routing.eps.or.at/appl/epsSO/transinit/eps/v2_7/bbd44f4d-609b-454e-8d5a-e0d1ac21f15a',
                'AT',
                ['EPG', 'EPN'],
                'EPG',
                false
            ),
            new Bank(
                'BAWAATWWXXX',
                'BAWAG AG',
                'https://routing.eps.or.at/appl/epsSO/transinit/eps/v2_7/64669764-e6fc-401c-8f3d-26e9169ba6ff',
                'AT',
                ['EPG', 'EPN'],
                'EPG',
                false
            ),
            new Bank(
                'ASPKAT2LXXX',
                'Erste Bank und Sparkassen',
                'https://routing.eps.or.at/appl/epsSO/transinit/eps/v2_7/1d48e2f7-604c-4d1d-894e-170635d1a645',
                'AT',
                ['EPG', 'EPN'],
                'EPG',
                false
            ),
        ], $list->getBanks());

        $lastUrl = $this->http->getLastRequestInfo()['url'];
        $this->assertStringContainsString('/v2_6', $lastUrl);
    }

    public function testGetBanksV27Throws(): void
    {
        $this->mockResponse(200, $this->loadFixture('BankListWithApp2AppField.xml'));

        $list = $this->target->getBanks('2.7');
        $this->assertInstanceOf(BankList::class, $list);

        $this->assertEquals([
            new Bank(
                'BKAUATWWXXX',
                'Bank Austria',
                'https://routing.eps.or.at/appl/epsSO/transinit/eps/v2_7/bbd44f4d-609b-454e-8d5a-e0d1ac21f15a',
                'AT',
                ['EPG', 'EPN'],
                'EPG',
                false
            ),
            new Bank(
                'BAWAATWWXXX',
                'BAWAG AG',
                'https://routing.eps.or.at/appl/epsSO/transinit/eps/v2_7/64669764-e6fc-401c-8f3d-26e9169ba6ff',
                'AT',
                ['EPG', 'EPN'],
                'EPG',
                false
            ),
            new Bank(
                'ASPKAT2LXXX',
                'Erste Bank und Sparkassen',
                'https://routing.eps.or.at/appl/epsSO/transinit/eps/v2_7/1d48e2f7-604c-4d1d-894e-170635d1a645',
                'AT',
                ['EPG', 'EPN'],
                'EPG',
                false
            ),
        ], $list->getBanks());

        $lastUrl = $this->http->getLastRequestInfo()['url'];
        $this->assertStringContainsString('/v2_7', $lastUrl);
    }

    /**
     * @dataProvider provideSoUrls
     */
    public function testGetBanksWithDifferentSoUrls(string $soUrl, string $expectedUrl): void
    {
        $this->setUpCommunicator($soUrl);
        $this->mockResponse(200, $this->loadFixture('BankListSample.xml'));

        $banks = $this->target->getBanks('2.6');

        $this->assertInstanceOf(BankList::class, $banks);
        $this->assertEquals([
            new Bank(
                'TESTBANKXXX',
                'Testbank',
                'https://routing.eps.or.at/appl/epsSO/transinit/eps/v2_6/23ea3d14-278c-4e81-a021-d7b77492b611',
                'AT',
                ['EPG'],
                null,
                false
            ),
        ], $banks->getBanks());
        $this->assertEquals($expectedUrl, $this->http->getLastRequestInfo()['url']);
    }

    /* ============================================================
     * Error handling
     * ============================================================
     */

    public function testGetBankListReadError(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('GET https://routing.eps.or.at/appl/epsSO/data/haendler/v2_6 failed with HTTP 404');

        $this->mockResponse(404, 'Not found', ['Content-Type' => 'text/plain']);
        $this->target->getBanks('2.6');
    }

    public function testGetBanksThrowsValidationExceptionOnInvalidXml(): void
    {
        $this->mockResponse(200, 'invalidData');

        $this->expectException(XmlValidationException::class);
        $this->target->getBanks('2.6');
    }

    public function testGetBanksThrowsOnUnsupportedVersion(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported version');

        $this->target->getBanks('foo');
    }

    public function testGetBanksThrowsOnErrorResponse(): void
    {
        $this->mockResponse(200, $this->loadFixture('BankListError.xml'));

        $this->expectException(BankListException::class);
        $this->expectExceptionMessage('Error: Bank list retrieval failed');

        $this->target->getBanks('2.6');
    }

    /* ============================================================
     * Special cases
     * ============================================================
     */

    public function testOverrideDefaultBaseUrl(): void
    {
        $this->target->setBaseUrl('http://example.com');
        $this->mockResponse(200, $this->loadFixture('BankListSample.xml'));

        $this->target->getBanks('2.6');

        $this->assertEquals(
            'http://example.com/data/haendler/v2_6',
            $this->http->getLastRequestInfo()['url']
        );
    }

    public function testGetBanksHandlesEmptyList(): void
    {
        $this->mockResponse(200, $this->loadFixture('BankListEmpty.xml'));

        $banks = $this->target->getBanks('2.6');

        $this->assertInstanceOf(BankList::class, $banks);
        $this->assertEquals(new BankList([]), $banks);
    }
    
    
}

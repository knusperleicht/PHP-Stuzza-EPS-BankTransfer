<?php

declare(strict_types=1);

namespace Knusperleicht\EpsBankTransfer\Tests\Requests;

use Exception;
use JMS\Serializer\SerializerInterface;
use Knusperleicht\EpsBankTransfer\Exceptions\XmlValidationException;
use Knusperleicht\EpsBankTransfer\Requests\Parts\PaymentFlowUrls;
use Knusperleicht\EpsBankTransfer\Requests\Parts\WebshopArticle;
use Knusperleicht\EpsBankTransfer\Requests\TransferInitiatorDetails;
use Knusperleicht\EpsBankTransfer\Serializer\SerializerFactory;
use Knusperleicht\EpsBankTransfer\Tests\Helper\XmlFixtureTestTrait;
use Knusperleicht\EpsBankTransfer\Utilities\XmlValidator;
use PHPUnit\Framework\TestCase;

class InitiateTransferRequestParametrizedTest extends TestCase
{
    use XmlFixtureTestTrait;

    private const TEST_USER_ID = 'AKLJS231534';
    private const TEST_SECRET = 'topSecret';
    private const TEST_BIC = 'GAWIATW1XXX';
    private const TEST_NAME = 'Max Mustermann';
    private const TEST_ACCOUNT = 'AT611904300234573201';
    private const TEST_REFERENCE = '1234567890ABCDEFG';
    private const TEST_AMOUNT = 15000;
    private const TEST_DATE = '2007-03-16';
    private const TEST_REMITTANCE_ID = 'AT1234567890XYZ';
    private const TEST_ARTICLE_NAME = 'Toaster';
    private const TEST_ARTICLE_COUNT = 1;
    private const TEST_OFI_IDENTIFIER = 'TESTBANKXXX';
    private const TEST_EXPIRATION_MINUTES = 5;
    private const TEST_UNSTRUCTURED_REMITTANCE = 'Foo is not Bar';

    /** @var PaymentFlowUrls */
    private $transferMsgDetails;

    /** @var SerializerInterface */
    private $serializer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->transferMsgDetails = $this->createTransferMsgDetails();
        $this->serializer = SerializerFactory::create();
    }

    public static function provideVersions(): array
    {
        return [
            'v2_6' => ['2.6', 'toV26', 'V26/TransferInitiatorDetailsWithoutSignature.xml'],
            'v2_7' => ['2.7', 'toV27', 'V27/TransferInitiatorDetailsWithoutSignature.xml'],
        ];
    }

    /**
     * @dataProvider provideVersions
     * @throws Exception
     */
    public function testGenerateTransferInitiatorDetails(string $version, string $mapperMethod, string $fixturePath): void
    {
        $data = $this->createTransferInitiatorDetailsWithArticle();
        $xmlTree = $data->{$mapperMethod}();
        $xmlData = $this->serializer->serialize($xmlTree, 'xml');

        XmlValidator::validateEpsProtocol($xmlData, $version);
        $this->assertXmlEqualsFixture($fixturePath, $xmlData);
    }

    public static function provideVersionsWithOfiIdentifier(): array
    {
        return [
            'v2_6' => ['2.6', 'toV26', 'V26/TransferInitiatorDetailsWithoutSignatureAndOrderingCustomerOfiIdentifier.xml'],
            'v2_7' => ['2.7', 'toV27', 'V27/TransferInitiatorDetailsWithoutSignatureAndOrderingCustomerOfiIdentifier.xml'],
        ];
    }

    /**
     * @dataProvider provideVersionsWithOfiIdentifier
     * @throws Exception
     */
    public function testGenerateTransferInitiatorDetailsWithOfiIdentifier(string $version, string $mapperMethod, string $fixturePath): void
    {
        $data = $this->createTransferInitiatorDetailsWithArticle();
        $data->setOrderingCustomerOfiIdentifier(self::TEST_OFI_IDENTIFIER);

        $xmlTree = $data->{$mapperMethod}();
        $xmlData = $this->serializer->serialize($xmlTree, 'xml');

        XmlValidator::validateEpsProtocol($xmlData, $version);
        $this->assertXmlEqualsFixture($fixturePath, $xmlData);
    }

    /**
     * @dataProvider provideVersions
     * @throws Exception
     */
    public function testTransferInitiatorDetailsInvalidExpirationMinutes(string $version): void
    {
        $data = $this->createTransferInitiatorDetails();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expiration minutes value of "3" is not between 5 and 60.');
        $data->setExpirationMinutes(3);
    }

    /**
     * @dataProvider provideVersions
     * @throws Exception
     */
    public function testTransferInitiatorDetailsWithExpirationTime(string $version, string $mapperMethod): void
    {
        $data = $this->createTransferInitiatorDetails();
        $data->setExpirationMinutes(self::TEST_EXPIRATION_MINUTES);
        $data->setRemittanceIdentifier('Order1');

        $epsProtocolDetails = $data->{$mapperMethod}();
        $xmlData = $this->serializer->serialize($epsProtocolDetails, 'xml');

        XmlValidator::validateEpsProtocol($xmlData, $version);
        $this->assertStringContainsString('ExpirationTime', $xmlData);
    }

    /**
     * @dataProvider provideVersions
     * @throws XmlValidationException
     * @throws Exception
     */
    public function testTransferInitiatorDetailsWithUnstructuredRemittanceIdentifier(string $version, string $mapperMethod): void
    {
        $data = $this->createTransferInitiatorDetails();
        $data->setUnstructuredRemittanceIdentifier(self::TEST_UNSTRUCTURED_REMITTANCE);
        $data->setExpirationMinutes(self::TEST_EXPIRATION_MINUTES);
        $epsProtocolDetails = $data->{$mapperMethod}();

        $xmlData = $this->serializer->serialize($epsProtocolDetails, 'xml');

        XmlValidator::validateEpsProtocol($xmlData, $version);
        $this->assertStringContainsString('<epi:UnstructuredRemittanceIdentifier>' . self::TEST_UNSTRUCTURED_REMITTANCE . '</epi:UnstructuredRemittanceIdentifier>', $xmlData);
    }

    private function createTransferMsgDetails(): PaymentFlowUrls
    {
        return new PaymentFlowUrls(
            'http://10.18.70.8:7001/vendorconfirmation',
            'http://10.18.70.8:7001/transactionok?danke.asp',
            'http://10.18.70.8:7001/transactionnok?fehler.asp'
        );
    }

    private function createTransferInitiatorDetails(): TransferInitiatorDetails
    {
        return new TransferInitiatorDetails(
            self::TEST_USER_ID,
            self::TEST_SECRET,
            self::TEST_BIC,
            self::TEST_NAME,
            self::TEST_ACCOUNT,
            self::TEST_REFERENCE,
            self::TEST_AMOUNT,
            $this->transferMsgDetails,
            self::TEST_DATE
        );
    }

    /**
     * @throws Exception
     */
    private function createTransferInitiatorDetailsWithArticle(): TransferInitiatorDetails
    {
        $data = $this->createTransferInitiatorDetails();
        $data->setRemittanceIdentifier(self::TEST_REMITTANCE_ID);
        $data->addArticle(new WebshopArticle(self::TEST_ARTICLE_NAME, self::TEST_ARTICLE_COUNT, self::TEST_AMOUNT));
        return $data;
    }
}

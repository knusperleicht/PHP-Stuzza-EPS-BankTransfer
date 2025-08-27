<?php

namespace Externet\EpsBankTransfer\Tests;

use DOMDocument;
use Exception;
use Externet\EpsBankTransfer\Exceptions\XmlValidationException;
use Externet\EpsBankTransfer\Generated\Protocol\V26\EpsProtocolDetails;
use Externet\EpsBankTransfer\Requests\Parts\PaymentFlowUrls;
use Externet\EpsBankTransfer\Requests\Parts\WebshopArticle;
use Externet\EpsBankTransfer\Requests\InitiateTransferRequest;
use Externet\EpsBankTransfer\Utilities\Fingerprint;
use Externet\EpsBankTransfer\Utilities\XmlValidator;
use JMS\Serializer\SerializerBuilder;

class TransferInitiatorDetailsTest extends BaseTest
{
    private const TEST_USER_ID = 'AKLJS231534';
    private const TEST_SECRET = 'topSecret';
    private const TEST_BIC = 'GAWIATW1XXX';
    private const TEST_NAME = 'Max Mustermann';
    private const TEST_ACCOUNT = 'AT611904300234573201';
    private const TEST_REFERENCE = '1234567890ABCDEFG';
    private const TEST_AMOUNT = 15000;
    private const TEST_DATE = '2007-03-16';
    private const TEST_CURRENCY = 'EUR';
    private const TEST_REMITTANCE_ID = 'AT1234567890XYZ';
    private const TEST_ARTICLE_NAME = 'Toaster';
    private const TEST_ARTICLE_COUNT = 1;
    private const TEST_OFI_IDENTIFIER = 'TESTBANKXXX';
    private const TEST_EXPIRATION_MINUTES = 5;
    private const TEST_UNSTRUCTURED_REMITTANCE = 'Foo is not Bar';

    /** @var PaymentFlowUrls */
    private $transferMsgDetails;

    private $serializer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->transferMsgDetails = $this->createTransferMsgDetails();
        $this->serializer = SerializerBuilder::create()
            ->addMetadataDir(__DIR__ . '/../../config/serializer/Protocol/V26', 'Externet\EpsBankTransfer\Generated\Protocol\V26')
            ->addMetadataDir(__DIR__ . '/../../config/serializer/Protocol/V27', 'Externet\EpsBankTransfer\Generated\Protocol\V27')
            ->addMetadataDir(__DIR__ . '/../../config/serializer/Payment/V26', 'Externet\EpsBankTransfer\Generated\Payment\V26')
            ->addMetadataDir(__DIR__ . '/../../config/serializer/Payment/V27', 'Externet\EpsBankTransfer\Generated\Payment\V27')
            ->addMetadataDir(__DIR__ . '/../../config/serializer/AustrianRules', 'Externet\EpsBankTransfer\Generated\AustrianRules')
            ->addMetadataDir(__DIR__ . '/../../config/serializer/Epi', 'Externet\EpsBankTransfer\Generated\Epi')
            ->addMetadataDir(__DIR__ . '/../../config/serializer/Refund', 'Externet\EpsBankTransfer\Generated\Refund')
            ->addMetadataDir(__DIR__ . '/../../config/serializer/BankList', 'Externet\EpsBankTransfer\Generated\BankList')
            ->addMetadataDir(__DIR__ . '/../../config/serializer/XmlDsig', 'Externet\EpsBankTransfer\Generated\XmlDsig')
            ->build();
    }

    /**
     * @throws Exception
     */
    public function testGenerateTransferInitiatorDetails()
    {
        $data = $this->createTransferInitiatorDetailsWithArticle();
        $xmlData = $this->serializer->serialize($data, 'xml');
        XmlValidator::ValidateEpsProtocol($xmlData);
        $this->assertXmlEquals('TransferInitiatorDetailsWithoutSignature.xml', $xmlData);
    }

    /**
     * @throws Exception
     */
    public function testGenerateTransferInitiatorDetailsWithOfiIdentifier()
    {
        $data = $this->createTransferInitiatorDetailsWithArticle();
        $data->orderingCustomerOfiIdentifier = self::TEST_OFI_IDENTIFIER;

        $xmlData = $this->serializer->serialize($data, 'xml');
        XmlValidator::ValidateEpsProtocol($xmlData);
        $this->assertXmlEquals('TransferInitiatorDetailsWithoutSignatureAndOrderingCustomerOfiIdentifier.xml', $xmlData);
    }

    /**
     * @throws Exception
     */
    public function testTransferInitiatorDetailsInvalidExpirationMinutes()
    {
        $data = $this->createTransferInitiatorDetails();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expiration minutes value of "3" is not between 5 and 60.');
        $data->setExpirationMinutes(3);
    }

    /**
     * @throws Exception
     */
    public function testTransferInitiatorDetailsWithExpirationTime()
    {
        $data = $this->createTransferInitiatorDetails();
        $data->setExpirationMinutes(self::TEST_EXPIRATION_MINUTES);
        $data->remittanceIdentifier = 'Order1';

        $epsProtocolDetails = $data->buildEpsProtocolDetails();
        $xmlData = $this->serializer->serialize($epsProtocolDetails, 'xml');

        XmlValidator::ValidateEpsProtocol($xmlData);
        $this->assertStringContainsString('ExpirationTime', $xmlData);
    }

    /**
     * @throws XmlValidationException
     * @throws Exception
     */
    public function testTransferInitiatorDetailsWithUnstructuredRemittanceIdentifier()
    {
        $data = $this->createTransferInitiatorDetails();
        $data->unstructuredRemittanceIdentifier = self::TEST_UNSTRUCTURED_REMITTANCE;
        $data->setExpirationMinutes(self::TEST_EXPIRATION_MINUTES);
        $epsProtocolDetails = $data->buildEpsProtocolDetails();

        $xmlData = $this->serializer->serialize($epsProtocolDetails, 'xml');

        XmlValidator::ValidateEpsProtocol($xmlData);
        $this->assertStringContainsString('<epi:UnstructuredRemittanceIdentifier><![CDATA[' . self::TEST_UNSTRUCTURED_REMITTANCE . ']]></epi:UnstructuredRemittanceIdentifier>', $xmlData);
    }

    public function testMD5FingerprintIsCalculatedCorrectly()
    {
        $data = $this->createTransferInitiatorDetails();
        $remittanceIdentifier = "remittanceIdentifier";
        $data->remittanceIdentifier = $remittanceIdentifier;

        $actual = $data->getMD5Fingerprint();
        $expected = $this->calculateFingerprint($remittanceIdentifier);

        $this->assertEquals($expected, $actual, 'Expected MD5 Fingerprint to be equal');
    }

    public function testMD5FingerprintIsCalculatedCorrectlyWithAnUnstructuredRemittanceIdentifier()
    {
        $data = $this->createTransferInitiatorDetails();
        $unstructuredRemittanceIdentifier = 'unstructuredRemittanceIdentifier';
        $data->unstructuredRemittanceIdentifier = $unstructuredRemittanceIdentifier;

        $actual = $data->getMD5Fingerprint();
        $expected = $this->calculateFingerprint($unstructuredRemittanceIdentifier);

        $this->assertEquals($expected, $actual, 'Expected MD5 Fingerprint to be equal');
    }

    private function createTransferMsgDetails(): PaymentFlowUrls
    {
        return new PaymentFlowUrls(
            "http://10.18.70.8:7001/vendorconfirmation",
            "http://10.18.70.8:7001/transactionok?danke.asp",
            "http://10.18.70.8:7001/transactionnok?fehler.asp"
        );
    }

    private function createTransferInitiatorDetails(): InitiateTransferRequest
    {
        return new InitiateTransferRequest(
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
    private function createTransferInitiatorDetailsWithArticle(): EpsProtocolDetails
    {
        $data = $this->createTransferInitiatorDetails();
        $data->remittanceIdentifier = self::TEST_REMITTANCE_ID;
        $data->webshopArticles[] = new WebshopArticle(self::TEST_ARTICLE_NAME, self::TEST_ARTICLE_COUNT, self::TEST_AMOUNT);
        return $data->buildEpsProtocolDetails();
    }

    private function calculateFingerprint(string $remittanceIdentifier): string
    {
        return Fingerprint::calculateExpectedMD5Fingerprint(
            self::TEST_SECRET,
            self::TEST_DATE,
            self::TEST_REFERENCE,
            self::TEST_ACCOUNT,
            $remittanceIdentifier,
            self::TEST_AMOUNT,
            self::TEST_CURRENCY,
            self::TEST_USER_ID
        );
    }

    private function assertXmlEquals(string $expectedFile, string $actual): void
    {
        $eDom = new DOMDocument();
        $eDom->loadXML($this->getEpsData($expectedFile));
        $eDom->formatOutput = true;
        $eDom->preserveWhiteSpace = false;
        $eDom->normalizeDocument();

        $aDom = new DOMDocument();
        $aDom->loadXML($actual);
        $aDom->formatOutput = true;
        $aDom->preserveWhiteSpace = false;
        $aDom->normalizeDocument();

        $this->assertEquals($eDom->saveXML(), $aDom->saveXML());
    }
}
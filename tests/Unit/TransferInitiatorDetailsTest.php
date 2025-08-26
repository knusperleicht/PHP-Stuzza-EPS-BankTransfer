<?php

namespace Externet\EpsBankTransfer\Tests;

use DOMDocument;
use Externet\EpsBankTransfer\Exceptions\XmlValidationException;
use Externet\EpsBankTransfer\TransferInitiatorDetails;
use Externet\EpsBankTransfer\TransferMsgDetails;
use Externet\EpsBankTransfer\Utilities\Fingerprint;
use Externet\EpsBankTransfer\Utilities\XmlValidator;
use Externet\EpsBankTransfer\WebshopArticle;

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

    /** @var TransferMsgDetails */
    private $transferMsgDetails;

    protected function setUp(): void
    {
        parent::setUp();
        $this->transferMsgDetails = $this->createTransferMsgDetails();
    }

    public function testGenerateTransferInitiatorDetails()
    {
        $data = $this->createTransferInitiatorDetailsWithArticle();
        $this->assertXmlEquals('TransferInitiatorDetailsWithoutSignature.xml', $data->getSimpleXml()->asXML());
    }

    public function testGenerateTransferInitiatorDetailsWithOfiIdentifier()
    {
        $data = $this->createTransferInitiatorDetailsWithArticle();
        $data->orderingCustomerOfiIdentifier = self::TEST_OFI_IDENTIFIER;

        $this->assertXmlEquals('TransferInitiatorDetailsWithoutSignatureAndOrderingCustomerOfiIdentifier.xml', $data->getSimpleXml()->asXML());
    }

    public function testTransferInitiatorDetailsInvalidExpirationMinutes()
    {
        $data = $this->createTransferInitiatorDetails();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expiration minutes value of "3" is not between 5 and 60.');
        $data->setExpirationMinutes(3);
    }

    /**
     * @throws XmlValidationException
     * @throws \Exception
     */
    public function testTransferInitiatorDetailsWithExpirationTime()
    {
        $data = $this->createTransferInitiatorDetails();
        $data->setExpirationMinutes(self::TEST_EXPIRATION_MINUTES);
        $actual = $data->getSimpleXml()->asXML();

        XmlValidator::ValidateEpsProtocol($actual);
        $this->assertStringContainsString('ExpirationTime', $actual);
    }

    /**
     * @throws XmlValidationException
     * @throws \Exception
     */
    public function testTransferInitiatorDetailsWithUnstructuredRemittanceIdentifier()
    {
        $data = $this->createTransferInitiatorDetails();
        $data->unstructuredRemittanceIdentifier = self::TEST_UNSTRUCTURED_REMITTANCE;
        $data->setExpirationMinutes(self::TEST_EXPIRATION_MINUTES);
        $actual = $data->getSimpleXml()->asXML();

        XmlValidator::ValidateEpsProtocol($actual);
        $this->assertStringContainsString('UnstructuredRemittanceIdentifier>' . self::TEST_UNSTRUCTURED_REMITTANCE, $actual);
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

    private function createTransferMsgDetails(): TransferMsgDetails
    {
        $transferMsgDetails = new TransferMsgDetails(
            "http://10.18.70.8:7001/vendorconfirmation",
            "http://10.18.70.8:7001/transactionok?danke.asp",
            "http://10.18.70.8:7001/transactionnok?fehler.asp"
        );
        $transferMsgDetails->TargetWindowNok = $transferMsgDetails->TargetWindowOk = 'Mustershop';
        return $transferMsgDetails;
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

    private function createTransferInitiatorDetailsWithArticle(): TransferInitiatorDetails
    {
        $data = $this->createTransferInitiatorDetails();
        $data->remittanceIdentifier = self::TEST_REMITTANCE_ID;
        $data->webshopArticles[] = new WebshopArticle(self::TEST_ARTICLE_NAME, self::TEST_ARTICLE_COUNT, self::TEST_AMOUNT);
        return $data;
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
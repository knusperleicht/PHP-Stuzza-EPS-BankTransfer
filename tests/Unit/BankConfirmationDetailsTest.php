<?php
declare(strict_types=1);

namespace Externet\EpsBankTransfer\Tests;

use Exception;
use Externet\EpsBankTransfer\BankConfirmationDetails;
use SimpleXMLElement;

class BankConfirmationDetailsTest extends BaseTest
{
    private const EXPECTED_REMITTANCE_ID = 'AT1234567890XYZ';
    private const EXPECTED_PAYMENT_REFERENCE_ID = 'RIAT1234567890XYZ';
    private const EXPECTED_SESSION_ID = 'String';
    private const EXPECTED_STATUS_CODE = 'OK';
    private const EXPECTED_REFERENCE_ID = '1234567890ABCDEFG';
    private const EXPECTED_CUSTOMER_NAME = 'Customer Name';
    private const EXPECTED_CUSTOMER_ID = 'DE0815';
    private const EXPECTED_CUSTOMER_BIC = 'GERMAN2A';

    /**
     * @var array<string,SimpleXMLElement>
     */
    private $simpleXmls;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->simpleXmls = [
            'WithSignature' => new SimpleXMLElement($this->getEpsData('BankConfirmationDetailsWithSignature.xml')),
            'UnstructuredWithoutSignature' => new SimpleXMLElement($this->getEpsData('BankConfirmationDetailsWithoutSignatureUnstructuredRemittanceIdentifier.xml')),
            'WithoutSignature' => new SimpleXMLElement($this->getEpsData('BankConfirmationDetailsWithoutSignature.xml')),
            'UnstructuredWithSignature' => new SimpleXMLElement($this->getEpsData('BankConfirmationDetailsWithSignatureUnstructuredRemittanceIdentifier.xml')),
            'WithPaymentInititatorDetails' => new SimpleXMLElement($this->getEpsData('BankConfirmationDetailsWithPaymentInitiatorDetailsWithoutSignature.xml')),
        ];
    }

    /**
     * @dataProvider bankConfirmationDetailsDataProvider
     */
    public function testBankConfirmationDetails(string $key, array $expectedValues): void
    {
        $details = $this->makeDetails($key);

        foreach ($expectedValues as $method => $expected) {
            $this->assertSame($expected, $details->$method());
        }
    }

    public function bankConfirmationDetailsDataProvider(): array
    {
        return [
            'test with signature' => [
                'WithSignature',
                [
                    'getRemittanceIdentifier' => self::EXPECTED_REMITTANCE_ID,
                    'getPaymentReferenceIdentifier' => self::EXPECTED_PAYMENT_REFERENCE_ID,
                    'getSessionId' => self::EXPECTED_SESSION_ID,
                    'getStatusCode' => self::EXPECTED_STATUS_CODE,
                    'getReferenceIdentifier' => self::EXPECTED_REFERENCE_ID
                ]
            ],
            'test without signature' => [
                'UnstructuredWithoutSignature',
                [
                    'getRemittanceIdentifier' => self::EXPECTED_REMITTANCE_ID,
                    'getStatusCode' => self::EXPECTED_STATUS_CODE
                ]
            ],
            'test with payment initiator details' => [
                'WithPaymentInititatorDetails',
                [
                    'getOrderingCustomerNameAddress' => self::EXPECTED_CUSTOMER_NAME,
                    'getOrderingCustomerIdentifier' => self::EXPECTED_CUSTOMER_ID,
                    'getOrderingCustomerBIC' => self::EXPECTED_CUSTOMER_BIC
                ]
            ]
        ];
    }

    /**
     * Helper to create BankConfirmationDetails for a given test XML.
     */
    private function makeDetails(string $key): BankConfirmationDetails
    {
        return new BankConfirmationDetails($this->simpleXmls[$key]);
    }
}
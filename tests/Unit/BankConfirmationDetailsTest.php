<?php
declare(strict_types=1);

namespace Externet\EpsBankTransfer\Tests;

use Exception;
use Externet\EpsBankTransfer\BankConfirmationDetails;
use SimpleXMLElement;

class BankConfirmationDetailsTest extends BaseTest
{
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
     * @dataProvider remittanceIdentifierProvider
     */
    public function testGetRemittanceIdentifier(string $key): void
    {
        $details = $this->makeDetails($key);

        $this->assertSame('AT1234567890XYZ', $details->GetRemittanceIdentifier());

        if ($key !== 'WithSignature') {
            $this->assertSame('OK', $details->getStatusCode());
        }
    }

    public function remittanceIdentifierProvider(): array
    {
        return [
            'basic' => ['WithSignature'],
            'unstructured without signature' => ['UnstructuredWithoutSignature'],
            'with remittance without signature' => ['WithoutSignature'],
            'unstructured with signature' => ['UnstructuredWithSignature'],
        ];
    }

    public function testGetPaymentReferenceIdentifier(): void
    {
        $details = $this->makeDetails('WithSignature');
        $this->assertSame('RIAT1234567890XYZ', $details->getPaymentReferenceIdentifier());
    }

    public function testGetSessionId(): void
    {
        $details = $this->makeDetails('WithSignature');
        $this->assertSame('String', $details->getSessionId());
    }

    public function testGetStatusCode(): void
    {
        $details = $this->makeDetails('WithSignature');
        $this->assertSame('OK', $details->getStatusCode());
    }

    public function testGetReferenceIdentifier(): void
    {
        $details = $this->makeDetails('WithSignature');
        $this->assertSame('1234567890ABCDEFG', $details->getReferenceIdentifier());
    }

    public function testGetOrderingCustomerNameAddress(): void
    {
        $details = $this->makeDetails('WithPaymentInititatorDetails');
        $this->assertSame('Customer Name', $details->getOrderingCustomerNameAddress());
    }

    public function testGetOrderingCustomerIdentifier(): void
    {
        $details = $this->makeDetails('WithPaymentInititatorDetails');
        $this->assertSame('DE0815', $details->getOrderingCustomerIdentifier());
    }

    public function testGetOrderingCustomerBIC(): void
    {
        $details = $this->makeDetails('WithPaymentInititatorDetails');
        $this->assertSame('GERMAN2A', $details->getOrderingCustomerBIC());
    }

    /**
     * Helper to create BankConfirmationDetails for a given test XML.
     */
    private function makeDetails(string $key): BankConfirmationDetails
    {
        return new BankConfirmationDetails($this->simpleXmls[$key]);
    }
}

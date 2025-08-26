<?php
declare(strict_types=1);

namespace Externet\EpsBankTransfer\Tests;

use Externet\EpsBankTransfer\EpsRefundRequest;

class RefundRequestTest extends BaseTest
{

    /**
     * @dataProvider refundRequestDataProvider
     * @throws \Exception
     */
    public function testGenerateRefundRequestWithReason(
        string  $CreDtTm,
        string  $TransactionId,
        string  $MerchantIBAN,
        float   $Amount,
        string  $AmountCurrencyIdentifier,
        string  $UserId,
        string  $Pin,
        ?string $RefundReference,
        string  $expectedXmlFile
    )
    {

        $epsRefundRequest = new EpsRefundRequest(
            $CreDtTm,
            $TransactionId,
            $MerchantIBAN,
            $Amount,
            $AmountCurrencyIdentifier,
            $UserId,
            $Pin,
            $RefundReference
        );

        $aSimpleXml = $epsRefundRequest->getSimpleXml();

        $eDom = new \DOMDocument();
        $eDom->loadXML($this->getEpsData($expectedXmlFile));
        $eDom->formatOutput = true;
        $eDom->preserveWhiteSpace = false;
        $eDom->normalizeDocument();

        $this->assertEquals($eDom->saveXML(), $aSimpleXml->asXML());
    }

    public function refundRequestDataProvider(): array
    {
        return [
            [
                "2024-09-25T08:09:53.454+02:00",
                "epsJMG15K752",
                "AT175700054011014943",
                10.00,
                "EUR",
                "HYPTAT22XXX_143921",
                "fluxkompensator!",
                "REFUND-123456789",
                "RefundRequest.xml"
            ],
            [
                "2025-09-25T08:09:53.454+02:00",
                "epsJMG15K753",
                "AT175700054011014943",
                12.90,
                "EUR",
                "HYPTAT22XXX_143921",
                "fluxkompensator!",
                null,
                "RefundRequestWithoutReason.xml"
            ],
        ];
    }
}
<?php
declare(strict_types=1);

namespace Externet\EpsBankTransfer\Tests;

use DateTime;
use Exception;
use Externet\EpsBankTransfer\Generated\Refund\Amount;
use Externet\EpsBankTransfer\Generated\Refund\AuthenticationDetails;
use Externet\EpsBankTransfer\Generated\Refund\EpsRefundRequest;
use Externet\EpsBankTransfer\Utilities\Fingerprint;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerBuilder;

class RefundRequestTest extends BaseTest
{
    private $serializer;

    protected function setUp(): void
    {
        parent::setUp();
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
     * @dataProvider refundRequestDataProvider
     * @throws Exception
     */
    public function testGenerateRefundRequestWithReason(
        string  $CreDtTm,
        string  $TransactionId,
        string  $MerchantIBAN,
        float   $Amount,
        string  $AmountCurrencyIdentifier,
        string  $UserId,
        ?string $RefundReference,
        string  $expectedXmlFile
    )
    {
        $refundRequest = new EpsRefundRequest();
        $refundRequest->setCreDtTm(new DateTime($CreDtTm));
        $refundRequest->setTransactionId($TransactionId);
        $refundRequest->setMerchantIBAN($MerchantIBAN);

        $amount = new Amount($Amount);
        $amount->setAmountCurrencyIdentifier($AmountCurrencyIdentifier);
        $refundRequest->setAmount($amount);

        $authenticationDetails = new AuthenticationDetails();
        $authenticationDetails->setUserId($UserId);
        $authenticationDetails->setSHA256Fingerprint(
            Fingerprint::generateSHA256Fingerprint(
                "fluxkompensator!",
                $CreDtTm,
                $TransactionId,
                $MerchantIBAN,
                (string)$Amount,
                $AmountCurrencyIdentifier,
                $UserId,
                $RefundReference
            )
        );
        $refundRequest->setAuthenticationDetails($authenticationDetails);

        if ($RefundReference !== null) {
            $refundRequest->setRefundReference($RefundReference);
        }

        $xml = $this->serializer->serialize($refundRequest, 'xml');

        $eDom = new \DOMDocument();
        $eDom->loadXML($this->getEpsData($expectedXmlFile));
        $eDom->formatOutput = true;
        $eDom->preserveWhiteSpace = false;
        $eDom->normalizeDocument();

        $this->assertEquals($eDom->saveXML(), $xml);
    }

    public function refundRequestDataProvider(): array
    {
        return [
            'with_refund_reference' => [
                "2024-09-25T08:09:53.454+02:00",
                "epsJMG15K752",
                "AT175700054011014943",
                10.00,
                "EUR",
                "HYPTAT22XXX_143921",
                "REFUND-123456789",
                "RefundRequest.xml"
            ],
            'without_refund_reference' => [
                "2025-09-25T08:09:53.454+02:00",
                "epsJMG15K753",
                "AT175700054011014943",
                12.90,
                "EUR",
                "HYPTAT22XXX_143921",
                null,
                "RefundRequestWithoutReason.xml"
            ],
        ];
    }
}
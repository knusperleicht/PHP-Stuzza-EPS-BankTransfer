<?php
declare(strict_types=1);

namespace Knusperleicht\EpsBankTransfer\Tests\Requests;

use DateTime;
use Exception;
use Knusperleicht\EpsBankTransfer\Internal\Generated\Refund\Amount;
use Knusperleicht\EpsBankTransfer\Internal\Generated\Refund\AuthenticationDetails;
use Knusperleicht\EpsBankTransfer\Internal\Generated\Refund\EpsRefundRequest;
use Knusperleicht\EpsBankTransfer\Serializer\SerializerFactory;
use Knusperleicht\EpsBankTransfer\Tests\Helper\XmlFixtureTestTrait;
use Knusperleicht\EpsBankTransfer\Utilities\Fingerprint;
use JMS\Serializer\SerializerInterface;
use PHPUnit\Framework\TestCase;
use Knusperleicht\EpsBankTransfer\Utilities\MoneyFormatter;

class RefundRequestTest extends TestCase
{
    
    use XmlFixtureTestTrait;

    /** @var SerializerInterface */
    private $serializer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->serializer = SerializerFactory::create();
    }

    /**
     * @dataProvider refundRequestDataProvider
     * @throws Exception
     */
    public function testGenerateRefundRequestWithReason(
        string  $creationDateTime,
        string  $transactionId,
        string  $merchantIBAN,
                $amount,
        string  $amountCurrencyIdentifier,
        string  $userId,
        ?string $refundReference,
        string  $expectedXmlFile
    )
    {
        $refundRequest = new EpsRefundRequest();
        $refundRequest->setCreDtTm(new DateTime($creationDateTime));
        $refundRequest->setTransactionId($transactionId);
        $refundRequest->setMerchantIBAN($merchantIBAN);

        $amount = new Amount(MoneyFormatter::formatXsdDecimal($amount));
        $amount->setAmountCurrencyIdentifier($amountCurrencyIdentifier);
        $refundRequest->setAmount($amount);

        $authenticationDetails = new AuthenticationDetails();
        $authenticationDetails->setUserId($userId);
        $authenticationDetails->setSHA256Fingerprint(
            Fingerprint::generateSHA256Fingerprint(
                "fluxkompensator!",
                $creationDateTime,
                $transactionId,
                $merchantIBAN,
                (string)$amount,
                $amountCurrencyIdentifier,
                $userId,
                $refundReference
            )
        );
        $refundRequest->setAuthenticationDetails($authenticationDetails);

        if ($refundReference !== null) {
            $refundRequest->setRefundReference($refundReference);
        }

        $xml = $this->serializer->serialize($refundRequest, 'xml');

        $this->assertXmlEqualsFixture($expectedXmlFile, $xml);
    }

    public function refundRequestDataProvider(): array
    {
        return [
            'with_refund_reference' => [
                "2024-09-25T08:09:53.454+02:00",
                "epsJMG15K752",
                "AT175700054011014943",
                1000,
                "EUR",
                "HYPTAT22XXX_143921",
                "REFUND-123456789",
                "RefundRequest.xml"
            ],
            'without_refund_reference' => [
                "2025-09-25T08:09:53.454+02:00",
                "epsJMG15K753",
                "AT175700054011014943",
                1290,
                "EUR",
                "HYPTAT22XXX_143921",
                null,
                "RefundRequestWithoutReason.xml"
            ],
        ];
    }
}
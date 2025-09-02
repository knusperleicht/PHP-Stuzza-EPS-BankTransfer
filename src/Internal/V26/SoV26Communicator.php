<?php
declare(strict_types=1);

namespace Knusperleicht\EpsBankTransfer\Internal\V26;

use Exception;
use JMS\Serializer\SerializerInterface;
use Knusperleicht\EpsBankTransfer\Domain\BankConfirmationDetails;
use Knusperleicht\EpsBankTransfer\Domain\VitalityCheckDetails;
use Knusperleicht\EpsBankTransfer\Exceptions\CallbackResponseException;
use Knusperleicht\EpsBankTransfer\Exceptions\EpsException;
use Knusperleicht\EpsBankTransfer\Exceptions\InvalidCallbackException;
use Knusperleicht\EpsBankTransfer\Exceptions\XmlValidationException;
use Knusperleicht\EpsBankTransfer\Internal\Generated\BankList\EpsSOBankListProtocol;
use Knusperleicht\EpsBankTransfer\Internal\Generated\Protocol\V26\EpsProtocolDetails;
use Knusperleicht\EpsBankTransfer\Internal\Generated\Refund\EpsRefundResponse;
use Knusperleicht\EpsBankTransfer\Internal\SoCommunicatorCore;
use Knusperleicht\EpsBankTransfer\Requests\RefundRequest;
use Knusperleicht\EpsBankTransfer\Requests\TransferInitiatorDetails;
use Knusperleicht\EpsBankTransfer\Responses\ShopResponseDetails;
use Knusperleicht\EpsBankTransfer\Utilities\XmlValidator;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Internal communicator for EPS interface version 2.6.
 *
 * Encapsulates the low-level HTTP calls, XML serialization and validation
 * required by the EPS Scheme Operator for v2.6 endpoints (bank list, transfer
 * initiator, refund, confirmations).
 */
class SoV26Communicator extends \Knusperleicht\EpsBankTransfer\Internal\AbstractSoCommunicator
{
    public const BANKLIST = '/data/haendler/v2_6';
    public const REFUND = '/refund/eps/v2_6';
    public const TRANSFER = '/transinit/eps/v2_6';
    public const VERSION = '2.6';

    protected function getVersion(): string
    {
        return self::VERSION;
    }

    protected function getTransferPath(): string
    {
        return self::TRANSFER;
    }

    protected function protocolClassFqn(): string
    {
        return EpsProtocolDetails::class;
    }

    protected function serializeTransferInitiator($transferInitiatorDetails): string
    {
        return $this->serializer->serialize($transferInitiatorDetails->toV26(), 'xml');
    }

    protected function vitalityFromProtocol($protocol): ?VitalityCheckDetails
    {
        return $protocol->getVitalityCheckDetails() ? VitalityCheckDetails::fromV26($protocol->getVitalityCheckDetails()) : null;
    }

    protected function bankConfirmationFromProtocol($protocol): ?BankConfirmationDetails
    {
        return $protocol->getBankConfirmationDetails() ? BankConfirmationDetails::fromV26($protocol) : null;
    }

    protected function shopResponseXml(ShopResponseDetails $details): string
    {
        return $this->serializer->serialize($details->toV26(), 'xml');
    }


    /**
     * Retrieve the EPS bank list (v2.6).
     *
     * @param string|null $targetUrl Optional override of the bank list endpoint
     * @return EpsSOBankListProtocol Parsed list of SO banks
     * @throws XmlValidationException When response XML is not valid
     */
    public function getBanks(?string $targetUrl = null): EpsSOBankListProtocol
    {
        $targetUrl = $targetUrl ?? $this->core->getBaseUrl() . self::BANKLIST;
        $body = $this->core->getUrl($targetUrl, 'Requesting bank list');

        XmlValidator::validateBankList($body);

        return $this->serializer->deserialize($body, EpsSOBankListProtocol::class, 'xml');
    }

    /**
     * Send a refund request to EPS v2.6 endpoint.
     *
     * @param RefundRequest $refundRequest Domain refund request
     * @param string|null $targetUrl Optional override of the endpoint URL
     * @return EpsRefundResponse Parsed EPS refund response
     * @throws XmlValidationException When response XML is invalid
     * @throws Exception On underlying HTTP/serialization errors
     */
    public function sendRefundRequest(
        RefundRequest $refundRequest,
        ?string       $targetUrl = null
    ): EpsRefundResponse
    {
        $targetUrl = $targetUrl ?? $this->core->getBaseUrl() . self::REFUND;

        $xmlData = $this->serializer->serialize($refundRequest->toV26(), 'xml');
        $responseXml = $this->core->postUrl(
            $targetUrl,
            $xmlData,
            'Sending refund request to ' . $targetUrl
        );

        XmlValidator::validateEpsRefund($responseXml, self::VERSION);

        return $this->serializer->deserialize($responseXml, EpsRefundResponse::class, 'xml');
    }

}

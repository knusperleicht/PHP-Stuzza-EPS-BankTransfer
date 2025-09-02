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
class SoV26Communicator
{
    public const BANKLIST = '/data/haendler/v2_6';
    public const REFUND = '/refund/eps/v2_6';
    public const TRANSFER = '/transinit/eps/v2_6';
    public const VERSION = '2.6';

    /** @var SoCommunicatorCore */
    private $core;

    /** @var SerializerInterface */
    private $serializer;

    /**
     * Constructor.
     *
     * @param ClientInterface $httpClient PSR-18 HTTP client used for requests
     * @param RequestFactoryInterface $requestFactory PSR-17 request factory
     * @param StreamFactoryInterface $streamFactory PSR-17 stream factory
     * @param string $baseUrl Base URL of the EPS Scheme Operator endpoints
     * @param LoggerInterface|null $logger Optional PSR-3 logger
     */
    public function __construct(
        ClientInterface         $httpClient,
        RequestFactoryInterface $requestFactory,
        StreamFactoryInterface  $streamFactory,
        string                  $baseUrl,
        LoggerInterface         $logger = null
    )
    {
        $this->core = new SoCommunicatorCore(
            $httpClient,
            $requestFactory,
            $streamFactory,
            $baseUrl,
            $logger
        );

        $this->serializer = $this->core->getSerializer();
    }

    /**
     * Send a TransferInitiatorDetails request to EPS v2.6 endpoint.
     *
     * Serializes the given request, posts it to the Scheme Operator and
     * validates the response against the v2.6 EPS Protocol XSD.
     *
     * @param TransferInitiatorDetails $transferInitiatorDetails Domain request to send
     * @param string|null $targetUrl Optional override of the endpoint URL; defaults to baseUrl + TRANSFER
     * @return EpsProtocolDetails Deserialized protocol response
     * @throws XmlValidationException When response XML does not match the schema
     * @throws Exception On underlying HTTP/serialization errors
     */
    public function sendTransferInitiatorDetails(
        TransferInitiatorDetails $transferInitiatorDetails,
        ?string                  $targetUrl = null
    ): EpsProtocolDetails
    {
        $this->core->handleObscurityConfig($transferInitiatorDetails);

        $targetUrl = $targetUrl ?? $this->core->getBaseUrl() . self::TRANSFER;

        $xmlData = $this->serializer->serialize($transferInitiatorDetails->toV26(), 'xml');
        $response = $this->core->postUrl($targetUrl, $xmlData, 'Send payment order (v2.6)');

        XmlValidator::validateEpsProtocol($response, self::VERSION);

        return $this->serializer->deserialize($response, EpsProtocolDetails::class, 'xml');
    }

    /**
     * Handle incoming EPS confirmation/vitality callback request.
     *
     * Reads raw XML from input stream, validates it, dispatches to the provided
     * callback depending on whether the payload is a VitalityCheck or a
     * BankConfirmation. For VitalityCheck, the raw payload is echoed back.
     *
     * The provided callbacks MUST return true to indicate success; otherwise a
     * CallbackResponseException is thrown and an error response is written.
     *
     * @param callable|null $confirmationCallback function(string $rawXml, BankConfirmationDetails $details): bool
     * @param callable|null $vitalityCheckCallback function(string $rawXml, VitalityCheckDetails $details): bool
     * @param string $rawPostStream Input stream URI to read raw request XML from (default php://input)
     * @param string $outputStream Output stream URI to write response XML to (default php://output)
     * @throws InvalidCallbackException When callbacks are missing or not callable
     * @throws XmlValidationException When input XML is invalid
     * @throws CallbackResponseException When a callback returns a non-true value
     */
    public function handleConfirmationUrl(
        $confirmationCallback = null,
        $vitalityCheckCallback = null,
        string $rawPostStream = 'php://input',
        string $outputStream = 'php://output'
    ): void
    {
        try {
            if ($confirmationCallback === null || !is_callable($confirmationCallback)) {
                throw new InvalidCallbackException('ConfirmationCallback not callable or missing');
            }
            if ($vitalityCheckCallback !== null && !is_callable($vitalityCheckCallback)) {
                throw new InvalidCallbackException('VitalityCheckCallback not callable');
            }

            $rawXml = file_get_contents($rawPostStream);
            XmlValidator::validateEpsProtocol($rawXml, self::VERSION);

            $protocol = $this->serializer->deserialize($rawXml, EpsProtocolDetails::class, 'xml');

            if ($protocol->getVitalityCheckDetails() !== null) {
                $this->handleVitalityCheck(
                    $vitalityCheckCallback,
                    $rawXml,
                    VitalityCheckDetails::fromV26($protocol->getVitalityCheckDetails()),
                    $outputStream
                );
                return;
            }

            if ($protocol->getBankConfirmationDetails() !== null) {
                $v26 = BankConfirmationDetails::fromV26($protocol);
                $this->handleBankConfirmation(
                    $confirmationCallback,
                    $rawXml,
                    $v26,
                    $outputStream);
                return;
            }

            throw new XmlValidationException('Unknown confirmation details structure');

        } catch (Exception $e) {
            $this->handleException($e, $outputStream);
            throw $e;
        }
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

    /**
     * Handle a VitalityCheck callback.
     *
     * Invokes user callback (if provided). The callback must return true. On success
     * the raw XML is written back to the output stream as required by EPS.
     *
     * @param callable|null $callback function(string $rawXml, VitalityCheckDetails $details): bool
     * @param string $rawXml Raw request XML
     * @param VitalityCheckDetails $vitality Parsed vitality check details
     * @param string $outputStream Output stream URI
     * @throws CallbackResponseException When callback does not return true
     */
    private function handleVitalityCheck(?callable $callback, string $rawXml, VitalityCheckDetails $vitality, string $outputStream): void
    {
        if ($callback !== null) {
            if (call_user_func($callback, $rawXml, $vitality) !== true) {
                throw new CallbackResponseException('Vitality check callback must return true');
            }
        }
        file_put_contents($outputStream, $rawXml);
    }

    /**
     * Handle a BankConfirmation callback.
     *
     * Builds a ShopResponseDetails and expects the provided callback to return true.
     * On success the shop response is serialized and written to the output stream.
     *
     * @param callable $callback function(string $rawXml, BankConfirmationDetails $details): bool
     * @param string $rawXml Raw request XML
     * @param BankConfirmationDetails $confirmation Parsed bank confirmation details
     * @param string $outputStream Output stream URI
     * @throws CallbackResponseException When callback does not return true
     */
    private function handleBankConfirmation(callable                $callback, string $rawXml,
                                            BankConfirmationDetails $confirmation,
                                            string                  $outputStream): void
    {
        $shopConfirmationDetails = new ShopResponseDetails();
        $shopConfirmationDetails->setSessionId($confirmation->getSessionId());
        $shopConfirmationDetails->setStatusCode($confirmation->getStatusCode());
        $shopConfirmationDetails->setPaymentReferenceIdentifier(
            $confirmation->getPaymentReferenceIdentifier()
        );

        if (call_user_func($callback, $rawXml, $confirmation) !== true) {
            throw new CallbackResponseException('Confirmation callback must return true');
        }

        $xml = $this->serializer->serialize($shopConfirmationDetails->toV26(), 'xml');
        file_put_contents($outputStream, $xml);
    }

    /**
     * Convert an exception into a ShopResponseDetails XML and write to output.
     *
     * EPS requires a structured response even on errors; this helper takes any
     * thrown exception and serializes an error message accordingly.
     *
     * @param Exception $e The exception that occurred
     * @param string $outputStream Output stream URI
     */
    private function handleException(Exception $e, string $outputStream): void
    {
        $shopConfirmationDetails = new ShopResponseDetails();

        if ($e instanceof EpsException) {
            $shopConfirmationDetails->setErrorMessage($e->getMessage());
        } else {
            $shopConfirmationDetails->setErrorMessage('Exception "' . get_class($e) . '" occurred during confirmation handling');
        }

        file_put_contents($outputStream, $this->serializer->serialize($shopConfirmationDetails->toV26(), 'xml'));
    }
}

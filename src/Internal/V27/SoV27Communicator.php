<?php
declare(strict_types=1);

namespace Knusperleicht\EpsBankTransfer\Internal\V27;

use Exception;
use JMS\Serializer\SerializerInterface;
use Knusperleicht\EpsBankTransfer\Domain\BankConfirmationDetails;
use Knusperleicht\EpsBankTransfer\Domain\VitalityCheckDetails;
use Knusperleicht\EpsBankTransfer\Exceptions\CallbackResponseException;
use Knusperleicht\EpsBankTransfer\Exceptions\EpsException;
use Knusperleicht\EpsBankTransfer\Exceptions\InvalidCallbackException;
use Knusperleicht\EpsBankTransfer\Exceptions\XmlValidationException;
use Knusperleicht\EpsBankTransfer\Internal\Generated\BankList\EpsSOBankListProtocol;
use Knusperleicht\EpsBankTransfer\Internal\Generated\Protocol\V27\EpsProtocolDetails;
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
 * Internal communicator for EPS interface version 2.7.
 *
 * Note: Functionality is intentionally not implemented yet because the official
 * XSD 2.7 is pending. All public methods throw LogicException to make the
 * limitation explicit to integrators while keeping the public API forward-compatible.
 */
class SoV27Communicator
{
    public const TRANSFER = '/transinit/eps/v2_7';
    public const VERSION = '2.7';

    /** @var SoCommunicatorCore */
    private $core;

    /** @var SerializerInterface */
    private $serializer;

    /**
     * Create the v2.7 communicator with shared HTTP/serialization core.
     *
     * @param ClientInterface $httpClient PSR-18 HTTP client.
     * @param RequestFactoryInterface $requestFactory PSR-17 request factory.
     * @param StreamFactoryInterface $streamFactory PSR-17 stream factory.
     * @param string $baseUrl Base URL used to build endpoint URLs.
     * @param LoggerInterface|null $logger Optional PSR-3 logger.
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
     * Send a TransferInitiatorDetails request to EPS v2.7 endpoint.
     *
     * Serializes the given request, posts it to the Scheme Operator and
     * validates the response against the v2.7 EPS Protocol XSD.
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

        $xmlData = $this->serializer->serialize($transferInitiatorDetails->toV27(), 'xml');
        $response = $this->core->postUrl($targetUrl, $xmlData, 'Send payment order (v2.7)');

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
            // Validate callbacks
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
                    VitalityCheckDetails::fromV27($protocol->getVitalityCheckDetails()),
                    $outputStream
                );
                return;
            }

            if ($protocol->getBankConfirmationDetails() !== null) {
                $v27 = BankConfirmationDetails::fromV27($protocol);
                $this->handleBankConfirmation(
                    $confirmationCallback,
                    $rawXml,
                    $v27,
                    $outputStream);
                return;
            }

            throw new XmlValidationException('Unknown confirmation details structure');
        } catch (Exception $e) {
            $this->handleException($e, $outputStream);
            throw $e;
        }
    }

    private function handleVitalityCheck(?callable $callback, string $rawXml, VitalityCheckDetails $vitality, string $outputStream): void
    {
        if ($callback !== null) {
            if (call_user_func($callback, $rawXml, $vitality) !== true) {
                throw new CallbackResponseException('Vitality check callback must return true');
            }
        }
        file_put_contents($outputStream, $rawXml);
    }

    private function handleBankConfirmation(callable $callback, string $rawXml, BankConfirmationDetails $confirmation, string $outputStream): void
    {
        $shopConfirmationDetails = new ShopResponseDetails();
        $shopConfirmationDetails->setSessionId($confirmation->getSessionId());
        $shopConfirmationDetails->setStatusCode($confirmation->getStatusCode());
        $shopConfirmationDetails->setPaymentReferenceIdentifier($confirmation->getPaymentReferenceIdentifier());

        if (call_user_func($callback, $rawXml, $confirmation) !== true) {
            throw new CallbackResponseException('Confirmation callback must return true');
        }

        $xml = $this->serializer->serialize($shopConfirmationDetails->toV27(), 'xml');
        file_put_contents($outputStream, $xml);
    }

    private function handleException(Exception $e, string $outputStream): void
    {
        $shopConfirmationDetails = new ShopResponseDetails();

        if ($e instanceof EpsException) {
            $shopConfirmationDetails->setErrorMessage($e->getMessage());
        } else {
            $shopConfirmationDetails->setErrorMessage('Exception "' . get_class($e) . '" occurred during confirmation handling');
        }

        file_put_contents($outputStream, $this->serializer->serialize($shopConfirmationDetails->toV27(), 'xml'));
    }

    /**
     * Fetch the bank list using the v2.7 interface.
     *
     * Not implemented until XSD 2.7 is available.
     *
     * @param string|null $targetUrl Optional custom target URL instead of the default.
     * @return EpsSOBankListProtocol
     * @throws \LogicException Always thrown until v2.7 support is implemented.
     */
    public function getBanks(?string $targetUrl = null): EpsSOBankListProtocol
    {
        throw new \LogicException('Not implemented yet - use version 2.6');
    }

    /**
     * Send a refund request using the v2.7 interface.
     *
     * Not implemented until XSD 2.7 is available.
     *
     * @param RefundRequest $refundRequest Refund request details.
     * @param string|null $targetUrl Optional custom target URL instead of the default.
     * @return EpsRefundResponse
     * @throws \LogicException Always thrown until v2.7 support is implemented.
     */
    public function sendRefundRequest(
        RefundRequest $refundRequest,
        ?string       $targetUrl = null
    ): EpsRefundResponse
    {
        throw new \LogicException('Not implemented yet - use version 2.6');
    }
}

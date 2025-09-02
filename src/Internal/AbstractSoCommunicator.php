<?php
declare(strict_types=1);

namespace Knusperleicht\EpsBankTransfer\Internal;

use Exception;
use JMS\Serializer\SerializerInterface;
use Knusperleicht\EpsBankTransfer\Domain\BankConfirmationDetails;
use Knusperleicht\EpsBankTransfer\Domain\VitalityCheckDetails;
use Knusperleicht\EpsBankTransfer\Exceptions\CallbackResponseException;
use Knusperleicht\EpsBankTransfer\Exceptions\EpsException;
use Knusperleicht\EpsBankTransfer\Exceptions\InvalidCallbackException;
use Knusperleicht\EpsBankTransfer\Exceptions\XmlValidationException;
use Knusperleicht\EpsBankTransfer\Responses\ShopResponseDetails;
use Knusperleicht\EpsBankTransfer\Utilities\XmlValidator;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Abstract base for versioned internal communicators (v2.6, v2.7).
 *
 * Holds shared HTTP/serialization core and provides common implementations
 * for sending TransferInitiator requests and handling Confirmation/Vitality
 * callbacks. Concrete subclasses must provide version-specific hooks.
 */
abstract class AbstractSoCommunicator
{
    /** @var SoCommunicatorCore */
    protected $core;
    /** @var SerializerInterface */
    protected $serializer;

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
     * Send a TransferInitiatorDetails request to EPS endpoint for this version.
     *
     * @param mixed $transferInitiatorDetails Domain request (type differs per version namespace)
     * @param string|null $targetUrl Optional endpoint override
     * @return object Deserialized protocol response object (generated class per version)
     * @throws XmlValidationException
     * @throws Exception
     */
    public function sendTransferInitiatorDetails($transferInitiatorDetails, ?string $targetUrl = null): object
    {
        $this->core->handleObscurityConfig($transferInitiatorDetails);

        $targetUrl = $targetUrl ?? $this->core->getBaseUrl() . $this->getTransferPath();

        $xmlData = $this->serializeTransferInitiator($transferInitiatorDetails);
        $response = $this->core->postUrl($targetUrl, $xmlData, 'Send payment order (' . $this->getVersion() . ')');

        XmlValidator::validateEpsProtocol($response, $this->getVersion());

        $protocolClass = $this->protocolClassFqn();
        return $this->serializer->deserialize($response, $protocolClass, 'xml');
    }

    /**
     * Handle incoming EPS confirmation/vitality callback request (shared flow).
     *
     * @param callable|null $confirmationCallback
     * @param callable|null $vitalityCheckCallback
     * @param string $rawPostStream
     * @param string $outputStream
     * @throws InvalidCallbackException
     * @throws XmlValidationException
     * @throws CallbackResponseException
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
            XmlValidator::validateEpsProtocol($rawXml, $this->getVersion());

            $protocol = $this->serializer->deserialize($rawXml, $this->protocolClassFqn(), 'xml');

            $vitality = $this->vitalityFromProtocol($protocol);
            if ($vitality !== null) {
                $this->handleVitalityCheck($vitalityCheckCallback, $rawXml, $vitality, $outputStream);
                return;
            }

            $confirmation = $this->bankConfirmationFromProtocol($protocol);
            if ($confirmation !== null) {
                $this->handleBankConfirmation($confirmationCallback, $rawXml, $confirmation, $outputStream);
                return;
            }

            throw new XmlValidationException('Unknown confirmation details structure');
        } catch (Exception $e) {
            $this->handleException($e, $outputStream);
            throw $e;
        }
    }

    protected function handleVitalityCheck(?callable $callback, string $rawXml, VitalityCheckDetails $vitality, string $outputStream): void
    {
        if ($callback !== null) {
            if (call_user_func($callback, $rawXml, $vitality) !== true) {
                throw new CallbackResponseException('Vitality check callback must return true');
            }
        }
        file_put_contents($outputStream, $rawXml);
    }

    protected function handleBankConfirmation(callable $callback, string $rawXml, BankConfirmationDetails $confirmation, string $outputStream): void
    {
        $shopConfirmationDetails = new ShopResponseDetails();
        $shopConfirmationDetails->setSessionId($confirmation->getSessionId());
        $shopConfirmationDetails->setStatusCode($confirmation->getStatusCode());
        $shopConfirmationDetails->setPaymentReferenceIdentifier($confirmation->getPaymentReferenceIdentifier());

        if (call_user_func($callback, $rawXml, $confirmation) !== true) {
            throw new CallbackResponseException('Confirmation callback must return true');
        }

        $xml = $this->shopResponseXml($shopConfirmationDetails);
        file_put_contents($outputStream, $xml);
    }

    protected function handleException(Exception $e, string $outputStream): void
    {
        $shopConfirmationDetails = new ShopResponseDetails();

        if ($e instanceof EpsException) {
            $shopConfirmationDetails->setErrorMessage($e->getMessage());
        } else {
            $shopConfirmationDetails->setErrorMessage('Exception "' . get_class($e) . '" occurred during confirmation handling');
        }

        file_put_contents($outputStream, $this->shopResponseXml($shopConfirmationDetails));
    }

    // Abstract hooks to be implemented by concrete versioned communicators
    abstract protected function getVersion(): string;
    abstract protected function getTransferPath(): string;
    abstract protected function protocolClassFqn(): string;
    abstract protected function serializeTransferInitiator($transferInitiatorDetails): string;
    abstract protected function vitalityFromProtocol($protocol): ?VitalityCheckDetails;
    abstract protected function bankConfirmationFromProtocol($protocol): ?BankConfirmationDetails;
    abstract protected function shopResponseXml(ShopResponseDetails $details): string;
}

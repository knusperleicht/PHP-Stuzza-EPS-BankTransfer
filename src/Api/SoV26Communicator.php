<?php
declare(strict_types=1);

namespace Externet\EpsBankTransfer\Api;

use Exception;
use Externet\EpsBankTransfer\Exceptions\CallbackResponseException;
use Externet\EpsBankTransfer\Exceptions\InvalidCallbackException;
use Externet\EpsBankTransfer\Exceptions\ShopResponseException;
use Externet\EpsBankTransfer\Exceptions\XmlValidationException;
use Externet\EpsBankTransfer\Generated\BankList\EpsSOBankListProtocol;
use Externet\EpsBankTransfer\Generated\Protocol\V26\EpsProtocolDetails;
use Externet\EpsBankTransfer\Generated\Refund\EpsRefundResponse;
use Externet\EpsBankTransfer\Internal\SoCommunicatorCore;
use Externet\EpsBankTransfer\Requests\InitiateTransferRequest;
use Externet\EpsBankTransfer\Requests\RefundRequest;
use Externet\EpsBankTransfer\Responses\ShopResponseDetails;
use Externet\EpsBankTransfer\Utilities\XmlValidator;
use JMS\Serializer\SerializerInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;

class SoV26Communicator implements SoV26CommunicatorInterface
{
    public const TEST_MODE_URL = 'https://routing-test.eps.or.at/appl/epsSO';
    public const LIVE_MODE_URL = 'https://routing.eps.or.at/appl/epsSO';

    /** @var SoCommunicatorCore */
    private $core;

    /** @var SerializerInterface */
    private $serializer;

    public function __construct(
        ClientInterface $httpClient,
        RequestFactoryInterface $requestFactory,
        StreamFactoryInterface $streamFactory,
        string $baseUrl = self::LIVE_MODE_URL,
        ?LoggerInterface $logger = null
    ) {
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
     * @param string|null $targetUrl
     * @return EpsSOBankListProtocol
     * @throws XmlValidationException
     */
    public function getBanks(?string $targetUrl = null): EpsSOBankListProtocol
    {
        $targetUrl = $targetUrl ?? $this->core->getBaseUrl() . '/data/haendler/v2_6';
        $body = $this->core->getUrl($targetUrl, 'Requesting bank list');

        XmlValidator::ValidateBankList($body);

        /** @var EpsSOBankListProtocol $bankList */
        return $this->serializer->deserialize($body, EpsSOBankListProtocol::class, 'xml');
    }

    /**
     * @throws XmlValidationException
     */
    public function initiateTransferRequest(
        InitiateTransferRequest $transferInitiatorDetails,
        ?string $targetUrl = null
    ): EpsProtocolDetails {
        if ($transferInitiatorDetails->remittanceIdentifier !== null) {
            $transferInitiatorDetails->remittanceIdentifier =
                $this->core->appendHash($transferInitiatorDetails->remittanceIdentifier);
        }

        if ($transferInitiatorDetails->unstructuredRemittanceIdentifier !== null) {
            $transferInitiatorDetails->unstructuredRemittanceIdentifier =
                $this->core->appendHash($transferInitiatorDetails->unstructuredRemittanceIdentifier);
        }

        $targetUrl = $targetUrl ?? $this->core->getBaseUrl() . '/transinit/eps/v2_6';

        $xmlData = $this->serializer->serialize($transferInitiatorDetails, 'xml');
        $response = $this->core->postUrl($targetUrl, $xmlData, 'Send payment order');

        XmlValidator::ValidateEpsProtocol($response);

        /** @var EpsProtocolDetails $protocolDetails */
        return $this->serializer->deserialize($response, EpsProtocolDetails::class, 'xml');
    }

    /**
     * @throws InvalidCallbackException
     * @throws XmlValidationException
     * @throws ShopResponseException
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
                throw new InvalidCallbackException('confirmationCallback not callable or missing');
            }
            if ($vitalityCheckCallback !== null && !is_callable($vitalityCheckCallback)) {
                throw new InvalidCallbackException('vitalityCheckCallback not callable');
            }

            $rawXml = file_get_contents($rawPostStream);
            XmlValidator::ValidateEpsProtocol($rawXml);

            /** @var EpsProtocolDetails $protocol */
            $protocol = $this->serializer->deserialize($rawXml, EpsProtocolDetails::class, 'xml');
            $shopConfirmationDetails = new ShopResponseDetails();

            if ($protocol->getVitalityCheckDetails() !== null) {
                $this->handleVitalityCheck($vitalityCheckCallback, $rawXml, $protocol->getVitalityCheckDetails(), $outputStream);
                return;
            }

            if ($protocol->getBankConfirmationDetails() !== null) {
                $this->handleBankConfirmation($confirmationCallback, $rawXml, $protocol->getBankConfirmationDetails(), $shopConfirmationDetails, $outputStream);
                return;
            }

            throw new XmlValidationException('Unknown confirmation details structure');

        } catch (Exception $e) {
            $this->handleException($e, $outputStream);
            throw $e;
        }
    }

    /**
     * @throws CallbackResponseException
     */
    private function handleVitalityCheck(?callable $callback, string $rawXml, $vitality, string $outputStream): void
    {
        if ($callback !== null) {
            if (call_user_func($callback, $rawXml, $vitality) !== true) {
                throw new CallbackResponseException('Vitality check callback must return true');
            }
        }
        file_put_contents($outputStream, $rawXml);
    }

    /**
     * @throws CallbackResponseException
     */
    private function handleBankConfirmation(callable $callback, string $rawXml, $confirmation, ShopResponseDetails $response, string $outputStream): void
    {
        $response->setSessionId($confirmation->getSessionId());
        $response->setStatusCode($confirmation->getPaymentConfirmationDetails()->getStatusCode());
        $response->setPaymentReferenceIdentifier(
            $confirmation->getPaymentConfirmationDetails()->getPaymentReferenceIdentifier()
        );

        if (call_user_func($callback, $rawXml, $confirmation) !== true) {
            throw new CallbackResponseException('Confirmation callback must return true');
        }

        $xml = $this->serializer->serialize($response->buildShopResponseDetails(), 'xml');
        file_put_contents($outputStream, $xml);
    }

    private function handleException(Exception $e, string $outputStream): void
    {
        $shopConfirmationDetails = new ShopResponseDetails();

        if ($e instanceof ShopResponseException) {
            $shopConfirmationDetails->setErrorMsg($e->getShopResponseErrorMessage());
        } else {
            $shopConfirmationDetails->setErrorMsg('Exception "' . get_class($e) . '" occurred during confirmation handling');
        }

        file_put_contents($outputStream, $this->serializer->serialize($shopConfirmationDetails->buildShopResponseDetails(), 'xml'));
    }

    /**
     * @throws XmlValidationException
     */
    public function sendRefundRequest(
        RefundRequest $refundRequest,
        ?string $targetUrl = null
    ): EpsRefundResponse {
        $targetUrl = $targetUrl ?? $this->core->getBaseUrl() . '/refund/eps/v2_6';

        $xmlData = $this->serializer->serialize($refundRequest->buildEpsRefundRequest(), 'xml');
        $responseXml = $this->core->postUrl(
            $targetUrl,
            $xmlData,
            'Sending refund request to ' . $targetUrl
        );

        XmlValidator::ValidateEpsRefund($responseXml);

        /** @var EpsRefundResponse $refundResponse */
        return $this->serializer->deserialize($responseXml, EpsRefundResponse::class, 'xml');
    }

    public function setObscuritySuffixLength(int $obscuritySuffixLength): void
    {
        $this->core->setObscuritySuffixLength($obscuritySuffixLength);
    }

    public function setObscuritySeed(?string $obscuritySeed): void
    {
        $this->core->setObscuritySeed($obscuritySeed);
    }

    public function setBaseUrl(string $baseUrl): void
    {
        $this->core->setBaseUrl($baseUrl);
    }
}

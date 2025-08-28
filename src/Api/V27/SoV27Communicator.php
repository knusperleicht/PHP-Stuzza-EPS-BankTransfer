<?php
declare(strict_types=1);

namespace Externet\EpsBankTransfer\Api\V27;

use Externet\EpsBankTransfer\Exceptions\XmlValidationException;
use Externet\EpsBankTransfer\Generated\BankList\EpsSOBankListProtocol;
use Externet\EpsBankTransfer\Generated\Protocol\V27\EpsProtocolDetails;
use Externet\EpsBankTransfer\Generated\Refund\EpsRefundResponse;
use Externet\EpsBankTransfer\Internal\SoCommunicatorCore;
use Externet\EpsBankTransfer\Requests\InitiateTransferRequest;
use Externet\EpsBankTransfer\Requests\RefundRequest;
use Externet\EpsBankTransfer\Utilities\XmlValidator;
use JMS\Serializer\SerializerInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;

class SoV27Communicator implements SoV27CommunicatorInterface
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

    public function getBanks(?string $targetUrl = null): EpsSOBankListProtocol
    {
        throw new \LogicException('Not implemented yet - waiting for XSD 2.7');
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

        $targetUrl = $targetUrl ?? $this->core->getBaseUrl() . '/transinit/eps/v2_7';

        $xmlData = $this->serializer->serialize($transferInitiatorDetails, 'xml');
        $response = $this->core->postUrl($targetUrl, $xmlData, 'Send payment order');

        XmlValidator::ValidateEpsProtocol($response, "V27");

        /** @var EpsProtocolDetails $protocolDetails */
        return $this->serializer->deserialize($response, EpsProtocolDetails::class, 'xml');
    }

    public function handleConfirmationUrl(
        $confirmationCallback = null,
        $vitalityCheckCallback = null,
        string $rawPostStream = 'php://input',
        string $outputStream = 'php://output'
    ): void {
        throw new \LogicException('Not implemented yet - waiting for XSD 2.7');
    }

    public function sendRefundRequest(
        RefundRequest $refundRequest,
        ?string $targetUrl = null
    ): EpsRefundResponse {
        throw new \LogicException('Not implemented yet - waiting for XSD 2.7');
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

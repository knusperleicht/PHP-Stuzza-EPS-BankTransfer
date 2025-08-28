<?php
declare(strict_types=1);

namespace Externet\EpsBankTransfer\Api;

use Externet\EpsBankTransfer\Generated\BankList\EpsSOBankListProtocol;
use Externet\EpsBankTransfer\Generated\Refund\EpsRefundResponse;
use Externet\EpsBankTransfer\Requests\RefundRequest;
use Externet\EpsBankTransfer\Utilities\XmlValidator;
use Externet\EpsBankTransfer\Internal\SoCommunicatorCore;
use JMS\Serializer\SerializerInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;
use Externet\EpsBankTransfer\Exceptions\XmlValidationException;

abstract class AbstractSoCommunicator
{

    public const TEST_MODE_URL = 'https://routing-test.eps.or.at/appl/epsSO';
    public const LIVE_MODE_URL = 'https://routing.eps.or.at/appl/epsSO';

    /** @var SoCommunicatorCore */
    protected $core;

    /** @var SerializerInterface */
    protected $serializer;

    public function __construct(
        ClientInterface         $httpClient,
        RequestFactoryInterface $requestFactory,
        StreamFactoryInterface  $streamFactory,
        string                  $baseUrl,
        ?LoggerInterface        $logger = null
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
     * Bank list is always 2.6
     *
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
     * Refund is always 2.6
     *
     * @throws XmlValidationException
     */
    public function sendRefundRequest(
        RefundRequest $refundRequest,
        ?string       $targetUrl = null
    ): EpsRefundResponse
    {
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

    protected function getSerializer(): SerializerInterface
    {
        return $this->serializer;
    }
}
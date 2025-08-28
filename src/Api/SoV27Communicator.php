<?php
declare(strict_types=1);

namespace Externet\EpsBankTransfer\Api;

use Exception;
use Externet\EpsBankTransfer\Domain\BankConfirmationDetails;
use Externet\EpsBankTransfer\Domain\ShopResponseDetails;
use Externet\EpsBankTransfer\Domain\VitalityCheckDetails;
use Externet\EpsBankTransfer\Exceptions\CallbackResponseException;
use Externet\EpsBankTransfer\Exceptions\InvalidCallbackException;
use Externet\EpsBankTransfer\Exceptions\ShopResponseException;
use Externet\EpsBankTransfer\Generated\BankList\EpsSOBankListProtocol;
use Externet\EpsBankTransfer\Generated\Protocol\V27\EpsProtocolDetails;
use Externet\EpsBankTransfer\Generated\Refund\EpsRefundResponse;
use Externet\EpsBankTransfer\Internal\SoCommunicatorCore;
use Externet\EpsBankTransfer\Requests\InitiateTransferRequest;
use Externet\EpsBankTransfer\Requests\RefundRequest;
use Externet\EpsBankTransfer\Utilities\Constants;
use Externet\EpsBankTransfer\Utilities\XmlValidator;
use JMS\Serializer\SerializerInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;
use SimpleXMLElement;

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

    public function getBanks(): EpsSOBankListProtocol
    {
        throw new \LogicException('Not implemented yet - waiting for XSD 2.7');
    }

    public function sendTransferInitiatorDetails(
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

        XmlValidator::ValidateEpsProtocol($response);

        /** @var EpsProtocolDetails $protocolDetails */
        return $this->serializer->deserialize($response, EpsProtocolDetails::class, 'xml');
    }

    public function handleConfirmationUrl(
        $confirmationCallback = null,
        $vitalityCheckCallback = null,
        string $rawPostStream = 'php://input',
        string $outputStream = 'php://output'
    ): void {
        $shopResponseDetails = new ShopResponseDetails();

        try {
            // confirmationCallback ist Pflicht
            if ($confirmationCallback === null || !is_callable($confirmationCallback)) {
                throw new InvalidCallbackException('confirmationCallback not callable or missing');
            }
            if ($vitalityCheckCallback !== null && !is_callable($vitalityCheckCallback)) {
                throw new InvalidCallbackException('vitalityCheckCallback not callable');
            }

            $HTTP_RAW_POST_DATA = file_get_contents($rawPostStream);
            XmlValidator::ValidateEpsProtocol($HTTP_RAW_POST_DATA);

            $xml          = new SimpleXMLElement($HTTP_RAW_POST_DATA);
            $epspChildren = $xml->children(Constants::XMLNS_epsp);
            $firstChild   = $epspChildren[0]->getName();

            if ($firstChild === 'VitalityCheckDetails') {
                if ($vitalityCheckCallback !== null) {
                    $VitalityCheckDetails = new VitalityCheckDetails($xml);
                    if (call_user_func($vitalityCheckCallback, $HTTP_RAW_POST_DATA, $VitalityCheckDetails) !== true) {
                        throw new CallbackResponseException('Vitality check callback must return true');
                    }
                }
                file_put_contents($outputStream, $HTTP_RAW_POST_DATA);

            } elseif ($firstChild === 'BankConfirmationDetails') {
                $BankConfirmationDetails = new BankConfirmationDetails($xml);

                $BankConfirmationDetails->setRemittanceIdentifier(
                    $this->core->stripHash($BankConfirmationDetails->getRemittanceIdentifier())
                );

                $shopResponseDetails->SessionId   = $BankConfirmationDetails->getSessionId();
                $shopResponseDetails->StatusCode  = $BankConfirmationDetails->getStatusCode();
                $shopResponseDetails->PaymentReferenceIdentifier =
                    $BankConfirmationDetails->getPaymentReferenceIdentifier();

                if (call_user_func($confirmationCallback, $HTTP_RAW_POST_DATA, $BankConfirmationDetails) !== true) {
                    throw new CallbackResponseException('Confirmation callback must return true');
                }

                file_put_contents($outputStream, $shopResponseDetails->getSimpleXml()->asXML());
            }

        } catch (Exception $e) {
            if ($e instanceof ShopResponseException) {
                $shopResponseDetails->ErrorMsg = $e->getShopResponseErrorMessage();
            } else {
                $shopResponseDetails->ErrorMsg =
                    'Exception "' . get_class($e) . '" occurred during confirmation handling';
            }

            file_put_contents($outputStream, $shopResponseDetails->getSimpleXml()->asXML());
            throw $e;
        }
    }


    public function sendRefundRequest(
        RefundRequest $refundRequest,
        ?string $targetUrl = null,
        ?string $logMessage = null
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

<?php

namespace Externet\EpsBankTransfer\Api;

use Exception;
use Externet\EpsBankTransfer\BankConfirmationDetails;
use Externet\EpsBankTransfer\BankResponseDetails;
use Externet\EpsBankTransfer\EpsProtocolDetails;
use Externet\EpsBankTransfer\EpsRefundRequest;
use Externet\EpsBankTransfer\EpsRefundResponse;
use Externet\EpsBankTransfer\Exceptions\CallbackResponseException;
use Externet\EpsBankTransfer\Exceptions\InvalidCallbackException;
use Externet\EpsBankTransfer\Exceptions\ShopResponseException;
use Externet\EpsBankTransfer\Exceptions\UnknownRemittanceIdentifierException;
use Externet\EpsBankTransfer\Exceptions\XmlValidationException;
use Externet\EpsBankTransfer\ShopResponseDetails;
use Externet\EpsBankTransfer\TransferInitiatorDetails;
use Externet\EpsBankTransfer\Utilities\Constants;
use Externet\EpsBankTransfer\Utilities\XmlValidator;
use Externet\EpsBankTransfer\VitalityCheckDetails;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use RuntimeException;
use SimpleXMLElement;

class SoCommunicator implements SoCommunicatorInterface
{
    public const TEST_MODE_URL = 'https://routing-test.eps.or.at/appl/epsSO';
    public const LIVE_MODE_URL = 'https://routing.eps.or.at/appl/epsSO';

    /** @var callable|null */
    private $logCallback;

    /** @var int */
    private $obscuritySuffixLength = 0;

    /** @var string|null */
    private $obscuritySeed;

    /** @var string */
    private $baseUrl;

    /** @var ClientInterface */
    private $httpClient;

    /** @var RequestFactoryInterface */
    private $requestFactory;

    /** @var StreamFactoryInterface */
    private $streamFactory;

    public function __construct(
        ClientInterface         $httpClient,
        RequestFactoryInterface $requestFactory,
        StreamFactoryInterface  $streamFactory,
        string                  $baseUrl = self::LIVE_MODE_URL
    ) {
        $this->httpClient    = $httpClient;
        $this->requestFactory = $requestFactory;
        $this->streamFactory  = $streamFactory;
        $this->baseUrl        = $baseUrl;
    }

    /** {@inheritdoc}
     * @throws XmlValidationException
     * @throws Exception
     */
    public function getBanksArray(): array
    {
        $xmlBanks = new SimpleXMLElement($this->getBanks());
        $banks = [];

        foreach ($xmlBanks as $xmlBank) {
            $bezeichnung = (string) $xmlBank->bezeichnung;
            $banks[$bezeichnung] = [
                'bic'         => (string) $xmlBank->bic,
                'bezeichnung' => $bezeichnung,
                'land'        => (string) $xmlBank->land,
                'epsUrl'      => (string) $xmlBank->epsUrl,
            ];
        }

        return $banks;
    }

    /**
     * Test that TryGetBanksArray returns null when banks cannot be retrieved.
     *
     * @return void
     */
    public function tryGetBanksArray(): ?array
    {
        try {
            return $this->getBanksArray();
        } catch (\Exception $e) {
            $this->writeLog('Could not get bank array. ' . $e->getMessage());
            return null;
        }
    }

    /** {@inheritdoc}
     * @throws XmlValidationException
     */
    public function getBanks(bool $validateXml = true): string
    {
        $url  = $this->baseUrl . '/data/haendler/v2_6';
        $body = $this->getUrl($url, 'Requesting bank list');

        if ($validateXml) {
            XmlValidator::ValidateBankList($body);
        }

        return $body;
    }

    /** {@inheritdoc}
     * @throws XmlValidationException
     * @throws Exception
     */
    public function sendTransferInitiatorDetails(
        TransferInitiatorDetails $transferInitiatorDetails,
        ?string                  $targetUrl = null
    ): EpsProtocolDetails
    {
        if ($transferInitiatorDetails->remittanceIdentifier !== null) {
            $transferInitiatorDetails->remittanceIdentifier =
                $this->appendHash($transferInitiatorDetails->remittanceIdentifier);
        }

        if ($transferInitiatorDetails->unstructuredRemittanceIdentifier !== null) {
            $transferInitiatorDetails->unstructuredRemittanceIdentifier =
                $this->appendHash($transferInitiatorDetails->unstructuredRemittanceIdentifier);
        }

        $targetUrl = $targetUrl ?? $this->baseUrl . '/transinit/eps/v2_6';
        $xmlData = $transferInitiatorDetails->getSimpleXml()->asXML();

        $response = $this->postUrl($targetUrl, $xmlData, 'Send payment order');

        XmlValidator::ValidateEpsProtocol($response);

        $xml = new SimpleXMLElement($response);
        $soAnswer = $xml->children(Constants::XMLNS_epsp);
        $bankResponseDetails = new BankResponseDetails(
            (string)$soAnswer->BankResponseDetails->ClientRedirectUrl ?? '',
            (string)$soAnswer->BankResponseDetails->ErrorDetails->ErrorCode ?? '',
            (string)$soAnswer->BankResponseDetails->ErrorDetails->ErrorMsg ?? '',
            (string)$soAnswer->BankResponseDetails->TransactionId ?? '',
            (string)$soAnswer->BankResponseDetails->QrCodeUrl ?? ''
        );
        return new EpsProtocolDetails($bankResponseDetails);
    }

    /** {@inheritdoc}
     * @throws Exception
     */
    public function handleConfirmationUrl(
        $confirmationCallback = null,
        $vitalityCheckCallback = null,
        $rawPostStream = 'php://input',
        $outputStream = 'php://output'
    ): void {
        $shopResponseDetails = new ShopResponseDetails();

        try {
            $this->testCallability($confirmationCallback, 'confirmationCallback');
            if ($vitalityCheckCallback !== null) {
                $this->testCallability($vitalityCheckCallback, 'vitalityCheckCallback');
            }

            $HTTP_RAW_POST_DATA = file_get_contents($rawPostStream);
            XmlValidator::ValidateEpsProtocol($HTTP_RAW_POST_DATA);

            $xml          = new SimpleXMLElement($HTTP_RAW_POST_DATA);
            $epspChildren = $xml->children(Constants::XMLNS_epsp);
            $firstChild   = $epspChildren[0]->getName();

            if ($firstChild === 'VitalityCheckDetails') {
                $this->writeLog('Vitality Check');
                if ($vitalityCheckCallback !== null) {
                    $VitalityCheckDetails = new VitalityCheckDetails($xml);
                    $this->confirmationUrlCallback(
                        $vitalityCheckCallback,
                        'vitality check',
                        [$HTTP_RAW_POST_DATA, $VitalityCheckDetails]
                    );
                }
                file_put_contents($outputStream, $HTTP_RAW_POST_DATA);
            } elseif ($firstChild === 'BankConfirmationDetails') {
                $this->writeLog('Bank Confirmation');
                $BankConfirmationDetails = new BankConfirmationDetails($xml);

                $BankConfirmationDetails->setRemittanceIdentifier(
                    $this->stripHash($BankConfirmationDetails->getRemittanceIdentifier())
                );

                $shopResponseDetails->SessionId                = $BankConfirmationDetails->getSessionId();
                $shopResponseDetails->StatusCode              = $BankConfirmationDetails->getStatusCode();
                $shopResponseDetails->PaymentReferenceIdentifier =
                    $BankConfirmationDetails->getPaymentReferenceIdentifier();

                $this->writeLog(sprintf(
                    'Calling confirmationUrlCallback for remittance identifier "%s" with status code %s',
                    $BankConfirmationDetails->getRemittanceIdentifier(),
                    $BankConfirmationDetails->getStatusCode()
                ));

                $this->confirmationUrlCallback(
                    $confirmationCallback,
                    'confirmation',
                    [$HTTP_RAW_POST_DATA, $BankConfirmationDetails]
                );

                $this->writeLog('III-8 Confirming payment receipt');
                file_put_contents($outputStream, $shopResponseDetails->getSimpleXml()->asXml());
            }
        } catch (Exception $e) {
            $this->writeLog($e->getMessage());

            if ($e instanceof ShopResponseException) {
                $shopResponseDetails->ErrorMsg = $e->getShopResponseErrorMessage();
            } else {
                $shopResponseDetails->ErrorMsg =
                    'An exception of type "' . get_class($e) . '" occurred during handling of the confirmation url';
            }

            file_put_contents($outputStream, $shopResponseDetails->getSimpleXml()->asXml());
            throw $e;
        }
    }

    /** {@inheritdoc}
     * @throws Exception
     */
    public function sendRefundRequest(
        EpsRefundRequest $refundRequest,
        ?string          $targetUrl = null,
        ?string          $logMessage = null
    ): EpsRefundResponse
    {
        $targetUrl = $targetUrl ?? $this->baseUrl . '/refund/eps/v2_6';
        $xmlData = $refundRequest->getSimpleXml()->asXML();

        $response = $this->postUrl(
            $targetUrl,
            $xmlData,
            $logMessage ?? 'Sending refund request to ' . $targetUrl
        );

        XmlValidator::ValidateEpsRefund($response);

        $xml = new SimpleXMLElement($response);
        $soAnswer = $xml->children(Constants::XMLNS_epsr);
        return new EpsRefundResponse(
            (string)$soAnswer->StatusCode,
            (string)$soAnswer->ErrorMsg ?: null
        );
    }
    
    /** {@inheritdoc} */
    public function setLogCallback(callable $callback): void
    {
        $this->logCallback = $callback;
    }

    /** {@inheritdoc} */
    public function setObscuritySuffixLength(int $obscuritySuffixLength): void
    {
        $this->obscuritySuffixLength = $obscuritySuffixLength;
    }

    /** {@inheritdoc} */
    public function setObscuritySeed(?string $obscuritySeed): void
    {
        $this->obscuritySeed = $obscuritySeed;
    }

    /** {@inheritdoc} */
    public function setBaseUrl(string $baseUrl): void
    {
        $this->baseUrl = $baseUrl;
    }

    // ==========================
    // PRIVATE INTERNAL HELPERS
    // ==========================

    /**
     * @throws CallbackResponseException
     */
    private function confirmationUrlCallback(callable $callback, string $name, array $args): void
    {
        if (call_user_func_array($callback, $args) !== true) {
            throw new CallbackResponseException(
                'Cannot handle confirmation URL. The given ' . $name . ' callback did not return TRUE'
            );
        }
    }

    /**
     * @throws InvalidCallbackException
     */
    private function testCallability($callback, string $name): void
    {
        if (!is_callable($callback)) {
            throw new InvalidCallbackException(
                'Cannot handle confirmation URL. Callback "' . $name . '" is not callable'
            );
        }
    }

    private function getUrl(string $url, string $logMessage = ''): string
    {
        $this->writeLog($logMessage ?: 'GET ' . $url);
        try {
            $request = $this->requestFactory->createRequest('GET', $url)
                ->withHeader('Accept', 'application/xml,text/xml,*/*');
            $response = $this->httpClient->sendRequest($request);
        } catch (ClientExceptionInterface $e) {
            $this->writeLog($logMessage ?: 'GET ' . $url, false);
            throw new RuntimeException('GET request failed: ' . $e->getMessage(), 0, $e);
        }

        if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) {
            $this->writeLog($logMessage ?: 'GET ' . $url, false);
            throw new RuntimeException(sprintf('GET %s failed with HTTP %d', $url, $response->getStatusCode()));
        }

        $this->writeLog($logMessage ?: 'GET ' . $url, true);
        return (string)$response->getBody();
    }

    private function postUrl(string $url, string $xmlBody, string $logMessage = ''): string
    {
        $this->writeLog($logMessage ?: 'POST ' . $url);
        try {
            $stream = $this->streamFactory->createStream($xmlBody);
            $request = $this->requestFactory->createRequest('POST', $url)
                ->withHeader('Content-Type', 'application/xml; charset=UTF-8')
                ->withHeader('Accept', 'application/xml,text/xml,*/*')
                ->withBody($stream);

            $response = $this->httpClient->sendRequest($request);
        } catch (ClientExceptionInterface $e) {
            $this->writeLog($logMessage ?: 'POST ' . $url, false);
            throw new RuntimeException('POST request failed: ' . $e->getMessage(), 0, $e);
        }

        if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) {
            $this->writeLog($logMessage ?: 'POST ' . $url, false);

            throw new RuntimeException(sprintf('POST %s failed with HTTP %d', $url, $response->getStatusCode()));
        }

        $this->writeLog($logMessage ?: 'POST ' . $url, true);
        return (string)$response->getBody();
    }

    private function writeLog(string $message, ?bool $success = null): void
    {
        if (is_callable($this->logCallback)) {
            if ($success !== null) {
                $message = ($success ? "SUCCESS:" : "FAILED:") . ' ' . $message;
            }
            call_user_func($this->logCallback, $message);
        } else {
            error_log('[EPS] ' . $message);
        }
    }

    private function appendHash(string $string): string
    {
        if ($this->obscuritySuffixLength === 0) {
            return $string;
        }

        if (empty($this->obscuritySeed)) {
            throw new \UnexpectedValueException('No security seed set when using security suffix.');
        }

        $hash = base64_encode(crypt($string, $this->obscuritySeed));
        return $string . substr($hash, 0, $this->obscuritySuffixLength);
    }

    private function stripHash(string $suffixed): string
    {
        if ($this->obscuritySuffixLength === 0) {
            return $suffixed;
        }

        $remittanceIdentifier = substr($suffixed, 0, -$this->obscuritySuffixLength);
        if ($this->appendHash($remittanceIdentifier) !== $suffixed) {
            throw new UnknownRemittanceIdentifierException(
                'Unknown RemittanceIdentifier supplied: ' . $suffixed
            );
        }

        return $remittanceIdentifier;
    }
}

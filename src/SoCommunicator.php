<?php

namespace at\externet\eps_bank_transfer;

use at\externet\eps_bank_transfer\exceptions\CallbackResponseException;
use at\externet\eps_bank_transfer\exceptions\InvalidCallbackException;
use at\externet\eps_bank_transfer\exceptions\ShopResponseException;
use at\externet\eps_bank_transfer\exceptions\UnknownRemittanceIdentifierException;
use at\externet\eps_bank_transfer\exceptions\XmlValidationException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use RuntimeException;

/**
 * Communicates with the EPS scheme operator for bank transfers.
 *
 * Provides methods to:
 * - Retrieve available banks
 * - Send transfer initiator requests
 * - Handle confirmation callbacks
 * - Send refund requests
 *
 * Uses PSR-18 (HTTP client) and PSR-17 (request/stream factory) interfaces.
 */
class SoCommunicator
{
    /** @var string EPS test mode endpoint */
    private const TEST_MODE_URL = 'https://routing.eps.or.at/appl/epsSO-test';

    /** @var string EPS live mode endpoint */
    private const LIVE_MODE_URL = 'https://routing.eps.or.at/appl/epsSO';

    /**
     * Optional callback for logging messages.
     *
     * Signature: function(string $message): void
     *
     * @var callable|null
     */
    public $LogCallback;

    /**
     * Number of hash characters appended to the RemittanceIdentifier.
     * Requires {@see $ObscuritySeed} if greater than 0.
     *
     * @var int
     */
    public $ObscuritySuffixLength = 0;

    /**
     * Seed string used by the hash function for RemittanceIdentifier.
     *
     * @var string|null
     */
    public $ObscuritySeed;

    /**
     * Base URL for EPS system requests.
     * Defaults to LIVE or TEST mode depending on constructor argument.
     *
     * @var string
     */
    public $BaseUrl;

    /** @var ClientInterface|null PSR-18 HTTP client */
    private $HttpClient;

    /** @var RequestFactoryInterface|null PSR-17 request factory */
    private $RequestFactory;

    /** @var StreamFactoryInterface|null PSR-17 stream factory */
    private $StreamFactory;

    /**
     * Constructor.
     *
     * @param bool $testMode If true, uses EPS test endpoint.
     * @param ClientInterface|null $httpClient PSR-18 HTTP client.
     * @param RequestFactoryInterface|null $requestFactory PSR-17 request factory.
     * @param StreamFactoryInterface|null $streamFactory PSR-17 stream factory.
     */
    public function __construct(
        ClientInterface         $httpClient,
        RequestFactoryInterface $requestFactory,
        StreamFactoryInterface  $streamFactory,
        bool                    $testMode = false
    )
    {
        $this->BaseUrl = $testMode ? self::TEST_MODE_URL : self::LIVE_MODE_URL;
        $this->HttpClient = $httpClient;
        $this->RequestFactory = $requestFactory;
        $this->StreamFactory = $streamFactory;
    }

    /**
     * Safe wrapper around {@see GetBanksArray()}.
     * Returns null instead of throwing exceptions.
     *
     * @return array<string,array<string,string>>|null
     */
    public function TryGetBanksArray(): ?array
    {
        try {
            return $this->GetBanksArray();
        } catch (\Exception $e) {
            $this->WriteLog('Could not get bank array. ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Retrieves available banks from the EPS system.
     *
     * @return array<string,array<string,string>> Bank list indexed by bank name.
     *
     * @throws XmlValidationException If the EPS XML fails validation.
     * @throws \Exception On transport or parsing errors.
     */
    public function GetBanksArray(): array
    {
        $xmlBanks = new \SimpleXMLElement($this->GetBanks(true));
        $banks = [];

        foreach ($xmlBanks as $xmlBank) {
            $bezeichnung = (string)$xmlBank->bezeichnung;
            $banks[$bezeichnung] = [
                'bic' => (string)$xmlBank->bic,
                'bezeichnung' => $bezeichnung,
                'land' => (string)$xmlBank->land,
                'epsUrl' => (string)$xmlBank->epsUrl,
            ];
        }

        return $banks;
    }

    /**
     * Fetches the XML list of banks from EPS.
     *
     * @param bool $validateXml Whether to validate the response XML.
     *
     * @return string Raw XML response.
     *
     * @throws RuntimeException On HTTP or transport errors.
     */
    public function GetBanks(bool $validateXml = true): string
    {
        $url = $this->BaseUrl . '/data/haendler/v2_6';
        $body = $this->GetUrl($url, 'Requesting bank list');

        if ($validateXml) {
            XmlValidator::ValidateBankList($body);
        }

        return $body;
    }

    /**
     * Sends a payment initiation request to EPS.
     *
     * @param TransferInitiatorDetails $transferInitiatorDetails Payment request details.
     * @param string|null $targetUrl Optional target URL.
     *
     * @return string Raw XML response.
     *
     * @throws \UnexpectedValueException If using security suffix without seed.
     * @throws XmlValidationException If response validation fails.
     */
    public function SendTransferInitiatorDetails(
        TransferInitiatorDetails $transferInitiatorDetails,
        ?string                  $targetUrl = null
    ): string
    {
        if ($transferInitiatorDetails->RemittanceIdentifier !== null) {
            $transferInitiatorDetails->RemittanceIdentifier =
                $this->AppendHash($transferInitiatorDetails->RemittanceIdentifier);
        }

        if ($transferInitiatorDetails->UnstructuredRemittanceIdentifier !== null) {
            $transferInitiatorDetails->UnstructuredRemittanceIdentifier =
                $this->AppendHash($transferInitiatorDetails->UnstructuredRemittanceIdentifier);
        }

        $targetUrl = $targetUrl ?? $this->BaseUrl . '/transinit/eps/v2_6';
        $xmlData = $transferInitiatorDetails->GetSimpleXml()->asXML();

        $response = $this->PostUrl($targetUrl, $xmlData, 'Send payment order');

        XmlValidator::ValidateEpsProtocol($response);
        return $response;
    }

    /**
     * Handles confirmation callbacks from EPS.
     *
     * @param callable|null $confirmationCallback Callback for payment confirmation.
     * @param callable|null $vitalityCheckCallback Optional callback for vitality checks.
     * @param string $rawPostStream Input stream (default: php://input).
     * @param string $outputStream Output stream (default: php://output).
     *
     * @throws InvalidCallbackException If a callback is not callable.
     * @throws CallbackResponseException If a callback does not return TRUE.
     * @throws XmlValidationException If XML validation fails.
     * @throws UnknownRemittanceIdentifierException If hash mismatch occurs.
     * @throws ShopResponseException On shop-related errors.
     */
    public function HandleConfirmationUrl(
        $confirmationCallback = null,
        $vitalityCheckCallback = null,
        $rawPostStream = 'php://input',
        $outputStream = 'php://output'
    ): void
    {
        $shopResponseDetails = new ShopResponseDetails();
        try {
            $this->TestCallability($confirmationCallback, 'confirmationCallback');
            if ($vitalityCheckCallback != null) {
                $this->TestCallability($vitalityCheckCallback, 'vitalityCheckCallback');
            }

            $HTTP_RAW_POST_DATA = file_get_contents($rawPostStream);
            XmlValidator::ValidateEpsProtocol($HTTP_RAW_POST_DATA);

            $xml = new \SimpleXMLElement($HTTP_RAW_POST_DATA);
            $epspChildren = $xml->children(XMLNS_epsp);
            $firstChildName = $epspChildren[0]->getName();

            if ($firstChildName === 'VitalityCheckDetails') {
                $this->WriteLog('Vitality Check');
                if ($vitalityCheckCallback != null) {
                    $VitalityCheckDetails = new VitalityCheckDetails($xml);
                    $this->ConfirmationUrlCallback(
                        $vitalityCheckCallback,
                        'vitality check',
                        [$HTTP_RAW_POST_DATA, $VitalityCheckDetails]
                    );
                }
                file_put_contents($outputStream, $HTTP_RAW_POST_DATA);
            } elseif ($firstChildName === 'BankConfirmationDetails') {
                $this->WriteLog('Bank Confirmation');
                $BankConfirmationDetails = new BankConfirmationDetails($xml);

                // Strip hash
                $BankConfirmationDetails->SetRemittanceIdentifier(
                    $this->StripHash($BankConfirmationDetails->GetRemittanceIdentifier())
                );

                $shopResponseDetails->SessionId = $BankConfirmationDetails->GetSessionId();
                $shopResponseDetails->StatusCode = $BankConfirmationDetails->GetStatusCode();
                $shopResponseDetails->PaymentReferenceIdentifier =
                    $BankConfirmationDetails->GetPaymentReferenceIdentifier();

                $this->WriteLog(sprintf(
                    'Calling confirmationUrlCallback for remittance identifier "%s" with status code %s',
                    $BankConfirmationDetails->GetRemittanceIdentifier(),
                    $BankConfirmationDetails->GetStatusCode()
                ));
                $this->ConfirmationUrlCallback(
                    $confirmationCallback,
                    'confirmation',
                    [$HTTP_RAW_POST_DATA, $BankConfirmationDetails]
                );

                $this->WriteLog('III-8 Confirming payment receipt');
                file_put_contents($outputStream, $shopResponseDetails->GetSimpleXml()->asXml());
            }
        } catch (\Exception $e) {
            $this->WriteLog($e->getMessage());

            if ($e instanceof ShopResponseException) {
                $shopResponseDetails->ErrorMsg = $e->GetShopResponseErrorMessage();
            } else {
                $shopResponseDetails->ErrorMsg =
                    'An exception of type "' . get_class($e) . '" occurred during handling of the confirmation url';
            }

            file_put_contents($outputStream, $shopResponseDetails->GetSimpleXml()->asXml());
            throw $e;
        }
    }

    /**
     * Executes the confirmation callback and validates return value.
     *
     * @param callable $callback The callback to execute.
     * @param string $name Name of the callback (for error reporting).
     * @param array $args Arguments to pass to the callback.
     *
     * @throws CallbackResponseException If callback does not return TRUE.
     */
    private function ConfirmationUrlCallback(callable $callback, string $name, array $args): void
    {
        if (call_user_func_array($callback, $args) !== true) {
            $message = 'The given ' . $name . ' confirmation callback function did not return TRUE';
            $fullMessage = 'Cannot handle confirmation URL. ' . $message;
            throw new CallbackResponseException($fullMessage);
        }
    }

    /**
     * Ensures a callback is callable.
     *
     * @param mixed $callback The callback reference.
     * @param string $name Name of the callback (for error reporting).
     *
     * @throws InvalidCallbackException If not callable.
     */
    private function TestCallability($callback, string $name): void
    {
        if (!is_callable($callback)) {
            $message = 'The given callback function for "' . $name . '" is not a callable';
            $fullMessage = 'Cannot handle confirmation URL. ' . $message;
            throw new InvalidCallbackException($fullMessage);
        }
    }

    /**
     * Performs an HTTP GET request.
     *
     * @param string $url Target URL.
     * @param string $logMessage Optional log message.
     *
     * @return string Response body.
     *
     * @throws RuntimeException On request/response errors.
     */
    private function GetUrl(string $url, string $logMessage = ''): string
    {
        $this->WriteLog($logMessage !== '' ? $logMessage : ('GET ' . $url));
        try {
            $request = $this->RequestFactory->createRequest('GET', $url)
                ->withHeader('Accept', 'application/xml,text/xml,*/*');
            $response = $this->HttpClient->sendRequest($request);
        } catch (ClientExceptionInterface $e) {
            $this->WriteLog($logMessage !== '' ? $logMessage : ('GET ' . $url), false);
            throw new RuntimeException('GET request failed: ' . $e->getMessage(), 0, $e);
        }

        $status = $response->getStatusCode();
        if ($status < 200 || $status >= 300) {
            $this->WriteLog($logMessage !== '' ? $logMessage : ('GET ' . $url), false);
            throw new RuntimeException(sprintf('GET %s failed with HTTP %d', $url, $status));
        }

        $this->WriteLog($logMessage !== '' ? $logMessage : ('GET ' . $url), true);
        return (string)$response->getBody();
    }

    /**
     * Performs an HTTP POST request with XML payload.
     *
     * @param string $url Target URL.
     * @param string $xmlBody XML payload.
     * @param string $logMessage Optional log message.
     *
     * @return string Response body.
     *
     * @throws RuntimeException On request/response errors.
     */
    private function PostUrl(string $url, string $xmlBody, string $logMessage = ''): string
    {
        $this->WriteLog($logMessage !== '' ? $logMessage : ('POST ' . $url));
        try {
            $stream = $this->StreamFactory->createStream($xmlBody);
            $request = $this->RequestFactory->createRequest('POST', $url)
                ->withHeader('Content-Type', 'application/xml; charset=UTF-8')
                ->withHeader('Accept', 'application/xml,text/xml,*/*')
                ->withBody($stream);
            $response = $this->HttpClient->sendRequest($request);
        } catch (ClientExceptionInterface $e) {
            $this->WriteLog($logMessage !== '' ? $logMessage : ('POST ' . $url), false);
            throw new RuntimeException('POST request failed: ' . $e->getMessage(), 0, $e);
        }

        $status = $response->getStatusCode();
        if ($status < 200 || $status >= 300) {
            $this->WriteLog($logMessage !== '' ? $logMessage : ('POST ' . $url), false);
            throw new RuntimeException(sprintf('POST %s failed with HTTP %d', $url, $status));
        }

        $this->WriteLog($logMessage !== '' ? $logMessage : ('POST ' . $url), true);
        return (string)$response->getBody();
    }

    /**
     * Writes a message to the log callback, if configured.
     *
     * @param string $message Log message.
     * @param bool|null $success If provided, prefixes message with SUCCESS/FAILED.
     */
    private function WriteLog(string $message, ?bool $success = null): void
    {
        if (is_callable($this->LogCallback)) {
            if ($success !== null) {
                $message = ($success ? "SUCCESS:" : "FAILED:") . ' ' . $message;
            }
            call_user_func($this->LogCallback, $message);
        }
    }

    /**
     * Appends a security hash suffix to a string.
     *
     * @param string $string Input string.
     *
     * @return string String with appended hash.
     *
     * @throws \UnexpectedValueException If suffix is enabled but seed not set.
     */
    private function AppendHash(string $string): string
    {
        if ($this->ObscuritySuffixLength == 0) {
            return $string;
        }

        if (empty($this->ObscuritySeed)) {
            throw new \UnexpectedValueException('No security seed set when using security suffix.');
        }

        $hash = base64_encode(crypt($string, $this->ObscuritySeed));
        return $string . substr($hash, 0, $this->ObscuritySuffixLength);
    }

    /**
     * Strips and validates a security hash suffix from a string.
     *
     * @param string $suffixed Input string with hash.
     *
     * @return string Original string without hash.
     *
     * @throws UnknownRemittanceIdentifierException If validation fails.
     */
    private function StripHash(string $suffixed): string
    {
        if ($this->ObscuritySuffixLength == 0) {
            return $suffixed;
        }

        $remittanceIdentifier = substr($suffixed, 0, -$this->ObscuritySuffixLength);
        if ($this->AppendHash($remittanceIdentifier) !== $suffixed) {
            throw new UnknownRemittanceIdentifierException(
                'Unknown RemittanceIdentifier supplied: ' . $suffixed
            );
        }

        return $remittanceIdentifier;
    }

    /**
     * Sends a refund request to EPS.
     *
     * @param EpsRefundRequest $refundRequest Refund request object.
     * @param string|null $targetUrl Optional target URL.
     * @param string|null $logMessage Optional log message.
     *
     * @return string Raw XML response.
     *
     * @throws XmlValidationException If response validation fails.
     * @throws RuntimeException On HTTP/transport errors.
     */
    public function SendRefundRequest(
        EpsRefundRequest $refundRequest,
        ?string          $targetUrl = null,
        ?string          $logMessage = null
    ): string
    {
        if ($targetUrl === null) {
            $targetUrl = $this->BaseUrl . '/refund/eps/v2_6';
        }

        $xmlData = $refundRequest->GetSimpleXml()->asXML();
        $response = $this->PostUrl(
            $targetUrl,
            $xmlData,
            $logMessage ?? 'Sending refund request to ' . $targetUrl
        );

        XmlValidator::ValidateEpsRefund($response);
        return $response;
    }
}

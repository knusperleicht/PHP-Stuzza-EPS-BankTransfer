<?php

namespace at\externet\eps_bank_transfer;

use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * Handles the communication with the EPS scheme operator for bank transfers
 */
class SoCommunicator
{
    /**
     * URL endpoint for test mode communications
     */
    const TEST_MODE_URL = 'https://routing.eps.or.at/appl/epsSO-test';

    /**
     * URL endpoint for live mode communications
     */
    const LIVE_MODE_URL = 'https://routing.eps.or.at/appl/epsSO';

    /**
     * Optional function to send log messages to
     * @var callable
     */
    public $LogCallback;

    /**
     * Number of hash characters to append to RemittanceIdentifier.
     * If set greater than 0, ObscuritySeed must also be set.
     * @var int
     */
    public $ObscuritySuffixLength = 0;

    /**
     * Seed string used by hash function for RemittanceIdentifier
     * @var string
     */
    public $ObscuritySeed;

    /**
     * Base URL for sending requests to the EPS system.
     * Defaults to LIVE_MODE_URL in production or TEST_MODE_URL in test mode.
     * @var string
     */
    public $BaseUrl;

    /**
     * PSR-18 compliant HTTP client for making requests
     * @var ClientInterface|null
     */
    private $HttpClient;

    /**
     * PSR-17 compliant request factory
     * @var RequestFactoryInterface|null
     */
    private $RequestFactory;

    /**
     * PSR-17 compliant stream factory
     * @var StreamFactoryInterface|null
     */
    private $StreamFactory;

    /**
     * Creates new instance of SoCommunicator
     *
     * @param bool $testMode Whether to use test mode endpoint
     * @param ClientInterface|null $httpClient PSR-18 HTTP client
     * @param RequestFactoryInterface|null $requestFactory PSR-17 request factory
     * @param StreamFactoryInterface|null $streamFactory PSR-17 stream factory
     */
    public function __construct($testMode = false, ClientInterface $httpClient = null, RequestFactoryInterface $requestFactory = null, StreamFactoryInterface $streamFactory = null)
    {
        $this->BaseUrl = $testMode ? self::TEST_MODE_URL : self::LIVE_MODE_URL;
        $this->HttpClient = $httpClient;
        $this->RequestFactory = $requestFactory;
        $this->StreamFactory = $streamFactory;
    }

    /**
     * Allows injecting/replacing the HTTP client (useful for tests or frameworks).
     * @param ClientInterface $client PSR-18 compliant HTTP client
     * @return void
     */
    public function setHttpClient(ClientInterface $client)
    {
        $this->HttpClient = $client;
    }

    /**
     * Allows injecting/replacing the request factory
     * @param RequestFactoryInterface $factory PSR-17 compliant request factory
     * @return void
     */
    public function setRequestFactory(RequestFactoryInterface $factory)
    {
        $this->RequestFactory = $factory;
    }

    /**
     * Allows injecting/replacing the stream factory
     * @param StreamFactoryInterface $factory PSR-17 compliant stream factory
     * @return void
     */
    public function setStreamFactory(StreamFactoryInterface $factory)
    {
        $this->StreamFactory = $factory;
    }

    /**
     * Failsafe version of GetBanksArray(). Catches and logs all exceptions.
     * @return array|null Array of bank details or null on error
     */
    public function TryGetBanksArray(): ?array
    {
        try {
            return $this->GetBanksArray();
        } catch (\Exception $e) {
            $this->WriteLog('Could not get Bank Array. ' . $e);
            return null;
        }
    }

    /**
     * Gets array of available banks from EPS system
     * @return array Array of bank details including BIC, name, country and EPS URL
     * @throws XmlValidationException If bank list XML validation fails
     * @throws \Exception On other errors
     */
    public function GetBanksArray(): array
    {
        $xmlBanks = new \SimpleXMLElement($this->GetBanks(true));
        $banks = array();
        foreach ($xmlBanks as $xmlBank) {
            $bezeichnung = '' . $xmlBank->bezeichnung;
            $banks[$bezeichnung] = array(
                'bic' => '' . $xmlBank->bic,
                'bezeichnung' => $bezeichnung,
                'land' => '' . $xmlBank->land,
                'epsUrl' => '' . $xmlBank->epsUrl,
            );
        }
        return $banks;
    }

    /**
     * Retrieves XML list of banks from EPS scheme operator
     *
     * @param bool $validateXml Whether to validate response against XSD schema
     * @return string Raw XML response
     * @throws XmlValidationException If XML validation fails
     * @throws \RuntimeException On HTTP/transport errors
     */
    public function GetBanks(bool $validateXml = true): string
    {
        $url = $this->BaseUrl . '/data/haendler/v2_6';
        $body = $this->GetUrl($url, 'Requesting bank list');

        if ($validateXml)
            XmlValidator::ValidateBankList($body);
        return $body;
    }

    /**
     * Sends payment initiation request to EPS scheme operator
     *
     * @param TransferInitiatorDetails $transferInitiatorDetails Payment details
     * @param string|null $targetUrl Optional custom endpoint URL
     * @return string Bank response XML
     * @throws \UnexpectedValueException When using security suffix without seed
     * @throws XmlValidationException When response validation fails
     */
    public function SendTransferInitiatorDetails(TransferInitiatorDetails $transferInitiatorDetails, string $targetUrl = null): string
    {
        if ($transferInitiatorDetails->RemittanceIdentifier != null)
            $transferInitiatorDetails->RemittanceIdentifier = $this->AppendHash($transferInitiatorDetails->RemittanceIdentifier);

        if ($transferInitiatorDetails->UnstructuredRemittanceIdentifier != null)
            $transferInitiatorDetails->UnstructuredRemittanceIdentifier = $this->AppendHash($transferInitiatorDetails->UnstructuredRemittanceIdentifier);

        if ($targetUrl === null)
            $targetUrl = $this->BaseUrl . '/transinit/eps/v2_6';

        $data = $transferInitiatorDetails->GetSimpleXml();
        $xmlData = $data->asXML();
        $response = $this->PostUrl($targetUrl, $xmlData, 'Send payment order');

        XmlValidator::ValidateEpsProtocol($response);
        return $response;
    }

    /**
     * Handles confirmation URL callbacks from EPS scheme operator
     *
     * @param callable $confirmationCallback Callback for bank confirmations
     * @param callable|null $vitalityCheckCallback Optional callback for vitality checks
     * @param string $rawPostStream Input stream for raw POST data
     * @param string $outputStream Output stream for responses
     * @throws InvalidCallbackException If callback is invalid
     * @throws CallbackResponseException If callback returns non-TRUE
     * @throws XmlValidationException If XML validation fails
     * @throws UnknownRemittanceIdentifierException If remittance ID hash mismatch
     * @throws \UnexpectedValueException When using security suffix without seed
     * @throws ShopResponseException On other errors
     */
    public function HandleConfirmationUrl($confirmationCallback, $vitalityCheckCallback = null, $rawPostStream = 'php://input', $outputStream = 'php://output')
    {
        $shopResponseDetails = new ShopResponseDetails();
        try {
            $this->TestCallability($confirmationCallback, 'confirmationCallback');
            if ($vitalityCheckCallback != null)
                $this->TestCallability($vitalityCheckCallback, 'vitalityCheckCallback');

            $HTTP_RAW_POST_DATA = file_get_contents($rawPostStream);
            XmlValidator::ValidateEpsProtocol($HTTP_RAW_POST_DATA);

            $xml = new \SimpleXMLElement($HTTP_RAW_POST_DATA);
            $epspChildren = $xml->children(XMLNS_epsp);
            $firstChildName = $epspChildren[0]->getName();

            if ($firstChildName == 'VitalityCheckDetails') {
                $this->WriteLog('Vitality Check');
                if ($vitalityCheckCallback != null) {
                    $VitalityCheckDetails = new VitalityCheckDetails($xml);
                    $this->ConfirmationUrlCallback($vitalityCheckCallback, 'vitality check', array($HTTP_RAW_POST_DATA, $VitalityCheckDetails));
                }

                // Step III-3: Confirm vitality check
                file_put_contents($outputStream, $HTTP_RAW_POST_DATA);
            } else if ($firstChildName == 'BankConfirmationDetails') {
                $this->WriteLog('Bank Confirmation');
                $BankConfirmationDetails = new BankConfirmationDetails($xml);

                // Strip security hash from remittance identifier
                $BankConfirmationDetails->SetRemittanceIdentifier($this->StripHash($BankConfirmationDetails->GetRemittanceIdentifier()));

                $shopResponseDetails->SessionId = $BankConfirmationDetails->GetSessionId();
                $shopResponseDetails->StatusCode = $BankConfirmationDetails->GetStatusCode();
                $shopResponseDetails->PaymentReferenceIdentifier = $BankConfirmationDetails->GetPaymentReferenceIdentifier();

                $this->WriteLog(sprintf('Calling confirmationUrlCallback for remittance identifier "%s" with status code %s', $BankConfirmationDetails->GetRemittanceIdentifier(), $BankConfirmationDetails->GetStatusCode()));
                $this->ConfirmationUrlCallback($confirmationCallback, 'confirmation', array($HTTP_RAW_POST_DATA, $BankConfirmationDetails));

                // Step III-8: Confirm payment receipt
                $this->WriteLog('III-8 Confirming payment receipt');
                file_put_contents($outputStream, $shopResponseDetails->GetSimpleXml()->asXml());
            }
        } catch (\Exception $e) {
            $this->WriteLog($e->getMessage());

            if (is_subclass_of($e, 'at\externet\eps_bank_transfer\ShopResponseException'))
                $shopResponseDetails->ErrorMsg = $e->GetShopResponseErrorMessage();
            else
                $shopResponseDetails->ErrorMsg = 'An exception of type "' . get_class($e) . '" occurred during handling of the confirmation url';

            file_put_contents($outputStream, $shopResponseDetails->GetSimpleXml()->asXml());

            throw $e;
        }
    }

    /**
     * Executes confirmation URL callback and validates return value
     *
     * @param callable $callback The callback to execute
     * @param string $name Callback name for error messages
     * @param array $args Arguments to pass to callback
     * @throws CallbackResponseException If callback does not return TRUE
     */
    private function ConfirmationUrlCallback($callback, $name, $args)
    {
        if (call_user_func_array($callback, $args) !== true) {
            $message = 'The given ' . $name . ' confirmation callback function did not return TRUE';
            $fullMessage = 'Cannot handle confirmation URL. ' . $message;
            throw new CallbackResponseException($fullMessage);
        }
    }

    /**
     * Validates that a callback is actually callable
     *
     * @param callable $callback The callback to test
     * @param string $name Callback name for error messages
     * @throws InvalidCallbackException If callback is not callable
     */
    private function TestCallability(&$callback, $name)
    {
        if (!is_callable($callback)) {
            $message = 'The given callback function for "' . $name . '" is not a callable';
            $fullMessage = 'Cannot handle confirmation URL. ' . $message;
            throw new InvalidCallbackException($fullMessage);
        }
    }

    /**
     * Makes HTTP GET request using configured PSR-18 client
     *
     * @param string $url Target URL
     * @param string $logMessage Optional log message
     * @return string Response body
     * @throws \RuntimeException On HTTP/transport errors
     */
    private function GetUrl($url, $logMessage = '')
    {
        $this->ensureHttpDependencies();
        $this->WriteLog($logMessage !== '' ? $logMessage : ('GET ' . $url));
        try {
            $request = $this->RequestFactory->createRequest('GET', $url)
                ->withHeader('Accept', 'application/xml,text/xml,*/*');
            $response = $this->HttpClient->sendRequest($request);
        } catch (ClientExceptionInterface $e) {
            $this->WriteLog($logMessage !== '' ? $logMessage : ('GET ' . $url), false);
            throw new \RuntimeException('GET request failed: ' . $e->getMessage(), 0, $e);
        }

        $status = $response->getStatusCode();
        if ($status < 200 || $status >= 300) {
            $this->WriteLog($logMessage !== '' ? $logMessage : ('GET ' . $url), false);
            throw new \RuntimeException(sprintf('GET %s failed with HTTP %d', $url, $status));
        }

        $this->WriteLog($logMessage !== '' ? $logMessage : ('GET ' . $url), true);
        return (string)$response->getBody();
    }

    /**
     * Makes HTTP POST request using configured PSR-18 client
     *
     * @param string $url Target URL
     * @param string $xmlBody XML request body
     * @param string $logMessage Optional log message
     * @return string Response body
     * @throws \RuntimeException On HTTP/transport errors
     */
    private function PostUrl($url, $xmlBody, $logMessage = '')
    {
        $this->ensureHttpDependencies();
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
            throw new \RuntimeException('POST request failed: ' . $e->getMessage(), 0, $e);
        }

        $status = $response->getStatusCode();
        if ($status < 200 || $status >= 300) {
            $this->WriteLog($logMessage !== '' ? $logMessage : ('POST ' . $url), false);
            throw new \RuntimeException(sprintf('POST %s failed with HTTP %d', $url, $status));
        }

        $this->WriteLog($logMessage !== '' ? $logMessage : ('POST ' . $url), true);
        return (string)$response->getBody();
    }

    /**
     * Writes message to configured log callback if available
     *
     * @param string $message Log message
     * @param bool|null $success Optional success flag to prefix message
     */
    private function WriteLog($message, $success = null)
    {
        if (is_callable($this->LogCallback)) {
            if ($success !== null)
                $message = ($success ? "SUCCESS:" : "FAILED:") . ' ' . $message;

            call_user_func($this->LogCallback, $message);
        }
    }

    /**
     * Appends security hash to input string if configured
     *
     * @param string $string Input string
     * @return string String with optional hash appended
     * @throws \UnexpectedValueException When using suffix without seed
     */
    private function AppendHash($string)
    {
        if ($this->ObscuritySuffixLength == 0)
            return $string;

        if (empty($this->ObscuritySeed))
            throw new \UnexpectedValueException('No security seed set when using security suffix.');

        $hash = base64_encode(crypt($string, $this->ObscuritySeed));
        return $string . substr($hash, 0, $this->ObscuritySuffixLength);
    }

    /**
     * Strips and validates security hash from input string
     *
     * @param string $suffixed Input string with hash
     * @return string Original string without hash
     * @throws UnknownRemittanceIdentifierException If hash validation fails
     */
    private function StripHash($suffixed)
    {
        if ($this->ObscuritySuffixLength == 0)
            return $suffixed;

        $remittanceIdentifier = substr($suffixed, 0, -$this->ObscuritySuffixLength);
        if ($this->AppendHash($remittanceIdentifier) != $suffixed)
            throw new UnknownRemittanceIdentifierException('Unknown RemittanceIdentifier supplied: ' . $suffixed);

        return $remittanceIdentifier;
    }

    /**
     * Executes a refund request using the EpsRefundRequest object
     *
     * @param EpsRefundRequest $refundRequest The refund request data
     * @param string|null $targetUrl Optional custom endpoint URL
     * @param string|null $logMessage Optional custom log message
     * @return string Response XML from refund request
     * @throws XmlValidationException If response validation fails
     * @throws \RuntimeException On HTTP/transport errors
     */
    public function ProcessRefund(EpsRefundRequest $refundRequest, string $targetUrl = null, string $logMessage = null): string
    {
        if ($targetUrl === null)
            $targetUrl = $this->BaseUrl . '/refund/eps/v2_6';

        $data = $refundRequest->GetSimpleXml();
        $xmlData = $data->asXML();

        $response = $this->PostUrl($targetUrl, $xmlData, $logMessage ?? 'Sending refund request to ' . $targetUrl);
        XmlValidator::ValidateEpsRefund($response);

        return $response;
    }

    /**
     * Ensures required HTTP dependencies are configured
     *
     * @throws \RuntimeException If dependencies missing
     */
    private function ensureHttpDependencies()
    {
        if (!$this->HttpClient || !$this->RequestFactory || !$this->StreamFactory) {
            throw new \RuntimeException(
                'HTTP client and PSR-17 factories are not configured. ' .
                'Please provide a PSR-18 ClientInterface and RequestFactoryInterface/StreamFactoryInterface ' .
                'via constructor or setHttpClient/setRequestFactory/setStreamFactory.'
            );
        }
    }
}
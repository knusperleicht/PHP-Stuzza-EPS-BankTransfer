<?php

namespace at\externet\eps_bank_transfer;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;


/**
 * Handles the communication with the EPS scheme operator
 */
class SoCommunicator
{
    const TEST_MODE_URL = 'https://routing.eps.or.at/appl/epsSO-test';
    const LIVE_MODE_URL = 'https://routing.eps.or.at/appl/epsSO';

    /**
     * Optional function to send log messages to
     * @var callable
     */
    public $LogCallback;

    /**
     * Number of hash chars to append to RemittanceIdentifier.
     * If set greater as 0 you'll also have to set a ObscuritySeed
     * @var int
     */
    public $ObscuritySuffixLength = 0;

    /**
     * Seed to be used by hash function for RemittanceIdentifier
     * @var string
     */
    public $ObscuritySeed;

    /**
     * The base url SoCommunicator sends requests to
     * Defaults to SoCommunicator::LIVE_MODE_URL when constructor is called with $testMode == false
     * Defaults to SoCommunicator::TEST_MODE_URL when constructor is called with $testMode == true
     */
    public $BaseUrl;

    /**
     * HTTP client (Guzzle)
     * @var Client
     */
    private $HttpClient;

    /**
     * Creates new Instance of SoCommunicator
     */
    public function __construct($testMode = false, Client $httpClient = null)
    {
        $this->BaseUrl = $testMode ? self::TEST_MODE_URL : self::LIVE_MODE_URL;
        $this->HttpClient = $httpClient ?: new Client([
            'timeout' => 10.0,
            'http_errors' => false, // handle non-2xx manually
        ]);
    }

    /**
     * Allows injecting/replacing the HTTP client (useful for tests with MockHandler).
     * @param Client $client
     * @return void
     */
    public function setHttpClient(Client $client)
    {
        $this->HttpClient = $client;
    }

    /**
     * Failsafe version of GetBanksArray(). All Exceptions will be swallowed
     * @return null or result of GetBanksArray()
     */
    public function TryGetBanksArray(): ?array
    {
        try
        {
            return $this->GetBanksArray();
        }
        catch (\Exception $e)
        {
            $this->WriteLog('Could not get Bank Array. ' . $e);
            return null;
        }
    }

    /**
     * @throws XmlValidationException
     * @throws \Exception
     */
    public function GetBanksArray(): array
    {
        $xmlBanks = new \SimpleXMLElement($this->GetBanks(true));
        $banks = array();
        foreach ($xmlBanks as $xmlBank)
        {
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
     * Get XML of banks from scheme operator.
     * Will throw an exception if data cannot be fetched, or XSD validation fails.
     * @param bool $validateXml validate against XSD
     * @return string
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
     * Sends the given TransferInitiatorDetails to the Scheme Operator
     * @param TransferInitiatorDetails $transferInitiatorDetails
     * @param string|null $targetUrl url with preselected bank identifier
     * @return string BankResponseDetails
     *@throws \UnexpectedValueException when using security suffix without security seed
     * @throws XmlValidationException when the returned BankResponseDetails does not validate against XSD
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
     * Call this function when the confirmation URL is called by the Scheme Operator.
     * The function will write ShopResponseDetails to the $outputStream in case of
     * BankConfirmationDetails.
     *
     * @param callable $confirmationCallback a callable to send BankConfirmationDetails to.
     * Will be called with the raw post data as first parameter and an Instance of
     * BankConfirmationDetails as second parameter. This callable must return TRUE.
     * @param callable|null $vitalityCheckCallback an optional callable for the vitalityCheck
     * Will be called with the raw post data as first parameter and an Instance of
     * VitalityCheckDetails as second parameter. This callable must return TRUE.
     * @param string $rawPostStream will read from this stream or file with file_get_contents
     * @param string $outputStream will write to this stream the expected responses for the
     * Scheme Operator
     * @throws InvalidCallbackException when callback is not callable
     * @throws CallbackResponseException when callback does not return TRUE
     * @throws XmlValidationException when $rawInputStream does not validate against XSD
     * @throws \UnexpectedValueException when using security suffix without security seed
     * @throws UnknownRemittanceIdentifierException|ShopResponseException when security suffix does not match
     */
    public function HandleConfirmationUrl($confirmationCallback, $vitalityCheckCallback = null, $rawPostStream = 'php://input', $outputStream = 'php://output')
    {
        $shopResponseDetails = new ShopResponseDetails();
        try
        {
            $this->TestCallability($confirmationCallback, 'confirmationCallback');
            if ($vitalityCheckCallback != null)
                $this->TestCallability($vitalityCheckCallback, 'vitalityCheckCallback');

            $HTTP_RAW_POST_DATA = file_get_contents($rawPostStream);
            XmlValidator::ValidateEpsProtocol($HTTP_RAW_POST_DATA);

            $xml = new \SimpleXMLElement($HTTP_RAW_POST_DATA);
            $epspChildren = $xml->children(XMLNS_epsp);
            $firstChildName = $epspChildren[0]->getName();

            if ($firstChildName == 'VitalityCheckDetails')
            {
                $this->WriteLog('Vitality Check');
                if ($vitalityCheckCallback != null)
                {
                    $VitalityCheckDetails = new VitalityCheckDetails($xml);
                    $this->ConfirmationUrlCallback($vitalityCheckCallback, 'vitality check', array($HTTP_RAW_POST_DATA, $VitalityCheckDetails));
                }

                // 7.1.9 Schritt III-3: Bestätigung Vitality Check Händler-eps SO
                file_put_contents($outputStream, $HTTP_RAW_POST_DATA);
            }
            else if ($firstChildName == 'BankConfirmationDetails')
            {
                $this->WriteLog('Bank Confirmation');
                $BankConfirmationDetails = new BankConfirmationDetails($xml);

                // Strip security hash from remittance identifier
                $BankConfirmationDetails->SetRemittanceIdentifier($this->StripHash($BankConfirmationDetails->GetRemittanceIdentifier()));

                $shopResponseDetails->SessionId = $BankConfirmationDetails->GetSessionId();
                $shopResponseDetails->StatusCode = $BankConfirmationDetails->GetStatusCode();
                $shopResponseDetails->PaymentReferenceIdentifier = $BankConfirmationDetails->GetPaymentReferenceIdentifier();

                $this->WriteLog(sprintf('Calling confirmationUrlCallback for remittance identifier "%s" with status code %s', $BankConfirmationDetails->GetRemittanceIdentifier(), $BankConfirmationDetails->GetStatusCode()));
                $this->ConfirmationUrlCallback($confirmationCallback, 'confirmation', array($HTTP_RAW_POST_DATA, $BankConfirmationDetails));

                // Schritt III-8: Bestätigung Erhalt eps Zahlungsbestätigung Händler-eps SO
                $this->WriteLog('III-8 Confirming payment receipt');
                file_put_contents($outputStream, $shopResponseDetails->GetSimpleXml()->asXml());
            }
        }
        catch (\Exception $e)
        {
            $this->WriteLog($e->getMessage());

            if (is_subclass_of($e, 'at\externet\eps_bank_transfer\ShopResponseException'))
                $shopResponseDetails->ErrorMsg = $e->GetShopResponseErrorMessage();
            else
                $shopResponseDetails->ErrorMsg = 'An exception of type "' . get_class($e) . '" occurred during handling of the confirmation url';

            file_put_contents($outputStream, $shopResponseDetails->GetSimpleXml()->asXml());

            throw $e;
        }
    }

    // Private functions

    /**
     * @throws CallbackResponseException
     */
    private function ConfirmationUrlCallback($callback, $name, $args)
    {
        if (call_user_func_array($callback, $args) !== true)
        {
            $message = 'The given ' . $name . ' confirmation callback function did not return TRUE';
            $fullMessage = 'Cannot handle confirmation URL. ' . $message;
            throw new CallbackResponseException($fullMessage);
        }
    }

    /**
     * @throws InvalidCallbackException
     */
    private function TestCallability(&$callback, $name)
    {
        if (!is_callable($callback))
        {
            $message = 'The given callback function for "' . $name . '" is not a callable';
            $fullMessage = 'Cannot handle confirmation URL. ' . $message;
            throw new InvalidCallbackException($fullMessage);
        }
    }

    /**
     * Internal: GET helper using Guzzle
     * @param string $url
     * @param string $logMessage
     * @return string
     * @throws \RuntimeException on HTTP/transport error
     */
    private function GetUrl(string $url, string $logMessage = ''): string
    {
        $this->WriteLog($logMessage !== '' ? $logMessage : ('GET ' . $url));
        try {
            $response = $this->HttpClient->request('GET', $url, [
                'headers' => [
                    'Accept' => 'application/xml,text/xml,*/*',
                ],
            ]);
        } catch (GuzzleException $e) {
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
     * Internal: POST helper using Guzzle
     * @param string $url
     * @param string $xmlBody
     * @param string $logMessage
     * @return string
     * @throws \RuntimeException on HTTP/transport error
     */
    private function PostUrl(string $url, string $xmlBody, string $logMessage = ''): string
    {
        $this->WriteLog($logMessage !== '' ? $logMessage : ('POST ' . $url));
        try {
            $response = $this->HttpClient->request('POST', $url, [
                'headers' => [
                    'Content-Type' => 'application/xml; charset=UTF-8',
                    'Accept' => 'application/xml,text/xml,*/*',
                ],
                'body' => $xmlBody,
            ]);
        } catch (GuzzleException $e) {
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

    private function WriteLog($message, $success = null)
    {
        if (is_callable($this->LogCallback))
        {
            if ($success !== null)
                $message = ($success ? "SUCCESS:" : "FAILED:") . ' ' . $message;

            call_user_func($this->LogCallback, $message);
        }
    }

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
     * @throws UnknownRemittanceIdentifierException
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
     * Executes a refund request using the EpsRefundRequest object.
     *
     * @param EpsRefundRequest $refundRequest The refund request data to be sent.
     * @param string|null $targetUrl Optional endpoint URL for EPS refund requests. Defaults to the base URL with refund path.
     * @param string|null $logMessage Optional custom log message for tracking the refund process.
     * @return string The response body from the refund request.
     */
    public function ProcessRefund(EpsRefundRequest $refundRequest, string $targetUrl = null, string $logMessage = null): string
    {
        $this->WriteLog($logMessage ?? 'Initiating refund request.');

        if ($targetUrl === null)
            $targetUrl = $this->BaseUrl . '/refund/eps/v2_6';

        $data = $refundRequest->GetSimpleXml();
        $xmlData = $data->asXML();

        $response = $this->PostUrl($targetUrl, $xmlData, $logMessage ?? 'Sending refund request to ' . $targetUrl);
        XmlValidator::ValidateEpsRefund($response);

        $this->WriteLog('Refund request completed successfully.');

        return $response;
    }

}

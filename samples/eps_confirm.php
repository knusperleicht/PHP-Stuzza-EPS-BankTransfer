<?php
/*
This file handles the confirmation call from the Scheme Operator (after a payment was received). It is called twice:
1. for Vitality-Check, according to "Abbildung 6-11: epsp:VitalityCheckDetails" (eps Pflichtenheft 2.5)
2. for the actual payment confirmation (Zahlungsbestätigung)
*/

require_once('../vendor/autoload.php');

use Externet\EpsBankTransfer\Api\SoCommunicator;
use Externet\EpsBankTransfer\BankConfirmationDetails;
use Nyholm\Psr7\Factory\Psr17Factory;
use Symfony\Component\HttpClient\Psr18Client;

/**
 * @param string $plainXml Raw XML message
 * @param Externet\EpsBankTransfer\BankConfirmationDetails $bankConfirmationDetails
 * @return true
 */
$paymentConfirmationCallback = function (string $plainXml, BankConfirmationDetails $bankConfirmationDetails) {
    // Handle "eps:StatusCode": "OK" or "NOK" or "VOK" or "UNKNOWN"
    if ($bankConfirmationDetails->GetStatusCode() == 'OK') {
        // TODO: Do your payment completion handling here
        // You should use $bankConfirmationDetails->GetRemittanceIdentifier();
    }

    // True is expected to be returned, otherwise the Scheme Operator will be informed that the server could not accept the payment confirmation
    return true;
};
try {
    $psr17Factory = new Psr17Factory();
    $soCommunicator = new SoCommunicator(
        new Psr18Client(),
        $psr17Factory,
        $psr17Factory,
        SoCommunicator::TEST_MODE_URL
    );
    $soCommunicator->HandleConfirmationUrl(
        $paymentConfirmationCallback,
        null,                 // Optional: a callback function which is called in case of Vitality-Check
        'php://input',        // This needs to be the raw post data received by the server. Change this only if you want to test this function with simulation data.
        'php://output'        // This needs to be the raw output stream which is sent to the Scheme Operator. Change this only if you want to test this function with simulation data.
    );
} catch (\Exception $e) {
    // Log error
    error_log($e->getMessage());
    // Return error response
    http_response_code(500);
}
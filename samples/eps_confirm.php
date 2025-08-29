<?php
declare(strict_types=1);
require_once('../vendor/autoload.php');

use Externet\EpsBankTransfer\Generated\Protocol\V26\BankConfirmationDetails;
use Externet\EpsBankTransfer\Internal\AbstractSoCommunicator;
use Externet\EpsBankTransfer\Internal\V26\SoV26Communicator;
use Nyholm\Psr7\Factory\Psr17Factory;
use Symfony\Component\HttpClient\Psr18Client;


$paymentConfirmationCallback = function (string $plainXml, BankConfirmationDetails $bankConfirmationDetails) {
    // Handle "eps:StatusCode": "OK" or "NOK" or "VOK" or "UNKNOWN"
    if ($bankConfirmationDetails->getPaymentConfirmationDetails()->getStatusCode() == 'OK') {
        // TODO: Do your payment completion handling here
        // You should use $bankConfirmationDetails->GetRemittanceIdentifier();
    }

    // True is expected to be returned, otherwise the Scheme Operator will be informed that the server could not accept the payment confirmation
    return true;
};
try {
    $psr17Factory = new Psr17Factory();
    $soCommunicator = new SoV26Communicator(
        new Psr18Client(),
        $psr17Factory,
        $psr17Factory,
        SoCommunicator::TEST_MODE_URL,
        new Monolog\Logger('eps')
    );
    $soCommunicator->handleConfirmationUrl(
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
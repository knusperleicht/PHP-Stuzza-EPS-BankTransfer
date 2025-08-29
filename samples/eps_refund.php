<?php
declare(strict_types=1);
require_once('../vendor/autoload.php');

use Externet\EpsBankTransfer\Internal\AbstractSoCommunicator;
use Externet\EpsBankTransfer\Internal\V26\SoV26Communicator;
use Externet\EpsBankTransfer\Requests\RefundRequest;
use Nyholm\Psr7\Factory\Psr17Factory;
use Symfony\Component\HttpClient\Psr18Client;

$userID = 'AKLJS231534';            // Eps "Händler-ID"/UserID = epsr:UserId
$pin = 'topSecret';                 // Secret for authentication / PIN = part of epsr:SHA256Fingerprint
$merchantIban = 'AT611904300234573201';

$refundRequest = new RefundRequest(
    date('Y-m-d\TH:i:s'),           // Current date-time (must not diverge more than 3hrs from SO time)
    'epsM7DPP3R12',            // EPS Transaction ID from epsp:BankResponse
    $merchantIban,
    "1.00",                       // Amount to refund (must be lower or equal original transaction amount)
    'EUR',            // Currency for amount (EPS Refund 1.0.1 only accepts EUR)
    $userID,
    $pin,
    'Refund Reason'         // RefundReference (optional) = Auftraggeberreferenz
);

// Send a refund request to Scheme Operator
$testMode = true;
$psr17Factory = new Psr17Factory();
$soCommunicator = new SoV26Communicator(
    new Psr18Client(),
    $psr17Factory,
    $psr17Factory,
    SoCommunicator::TEST_MODE_URL,
    new Monolog\Logger('eps')
);

try {
    $refundResponse = $soCommunicator->sendRefundRequest($refundRequest);

    echo $refundResponse->getStatusCode() . ', ' . $refundResponse->getErrorMsg();
    // Return code 000 (No Errors) only means the bank accepted the refund request.
    // A manual approval might be required.

} catch (\Exception $e) {
    echo 'Exception: ' . $e->getMessage();
}
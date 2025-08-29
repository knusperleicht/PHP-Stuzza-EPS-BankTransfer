<?php
declare(strict_types=1);
require_once('../vendor/autoload.php');

use Psa\EpsBankTransfer\Api\SoCommunicator;
use Psa\EpsBankTransfer\Requests\RefundRequest;
use Nyholm\Psr7\Factory\Psr17Factory;
use Symfony\Component\HttpClient\Psr18Client;

// === Refund configuration ===
$userID = 'AKLJS231534';                // EPS merchant (User) ID = epsr:UserId
$pin = 'topSecret';                     // PIN/secret used for refund authentication (part of epsr:SHA256Fingerprint)
$merchantIban = 'AT611904300234573201'; // Your merchant IBAN to receive/issue refunds

$refundRequest = new RefundRequest(
    date('Y-m-d\TH:i:s'),           // Current timestamp (must not differ more than ~3 hours from SO time)
    'epsM7DPP3R12',            // EPS Transaction ID (from epsp:BankResponse of the original payment)
    $merchantIban,
    '1.00',                        // Refund amount (must be <= original amount)
    'EUR',            // Currency (EPS Refund 1.0.1 only accepts EUR)
    $userID,
    $pin,
    'Refund Reason'         // Optional RefundReference (Auftraggeberreferenz)
);

// === Send the refund request to the EPS Scheme Operator (SO) ===
$testMode = true;
$psr17Factory = new Psr17Factory();
$soCommunicator = new SoCommunicator(
    new Psr18Client(),
    $psr17Factory,
    $psr17Factory,
    SoCommunicator::TEST_MODE_URL,   // Use LIVE base URL in production
    new Monolog\Logger('eps')
);

try {
    $refundResponse = $soCommunicator->sendRefundRequest($refundRequest);

    echo $refundResponse->getStatusCode() . ', ' . $refundResponse->getErrorMessage();
    // Note: Status code '000' (No Errors) means the bank accepted the refund request.
    // Depending on the bank, manual approval might still be required.
} catch (\Exception $e) {
    echo 'Exception: ' . $e->getMessage();
}
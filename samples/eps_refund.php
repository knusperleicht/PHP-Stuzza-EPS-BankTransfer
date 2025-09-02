<?php
declare(strict_types=1);
require_once('../vendor/autoload.php');

// Optional: Specify EPS interface version for all calls. Default is '2.6'.
// You can omit passing this constant to use the default.
const EPS_INTERFACE_VERSION = '2.6';

use Psa\EpsBankTransfer\Api\SoCommunicator;
use Psa\EpsBankTransfer\Exceptions\EpsException;
use Psa\EpsBankTransfer\Requests\RefundRequest;
use Nyholm\Psr7\Factory\Psr17Factory;
use Symfony\Component\HttpClient\Psr18Client;

// Load configuration
$config = file_exists(__DIR__ . '/config.local.php')
    ? require __DIR__ . '/config.local.php'
    : require __DIR__ . '/config.example.php';

// === Refund configuration ===
$userID = $config['user_id'];               // EPS merchant (User) ID = epsr:UserId
$pin = $config['pin'];                      // Merchant PIN/secret used to compute SHA-256 fingerprint (epsr:SHA256Fingerprint)
$merchantIban = $config['merchant_iban'];   // Your merchant IBAN to receive/issue refunds

$refundRequest = new RefundRequest(
    date('Y-m-d\TH:i:s.vP'),  // Current timestamp (must not differ more than ~3 hours from SO time)
    $config['sample_refund_transaction_id'],        // EPS Transaction ID (from epsp:BankResponse of the original payment)
    $merchantIban,
    1,                  // Refund amount in cents (must be <= original amount)
    'EUR',                // Currency (EPS Refund 1.0.1 only accepts EUR)
    $userID,
    $pin,
    'Refund Reason'       // Optional RefundReference (Auftraggeberreferenz)
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
    // Optional version can be passed as the 2nd argument; default is '2.6'
    $refundResponse = $soCommunicator->sendRefundRequest($refundRequest, EPS_INTERFACE_VERSION);

    echo $refundResponse->getStatusCode() . ', ' . $refundResponse->getErrorMessage();
    // Note: Status code '000' (No Errors) means the bank accepted the refund request.
    // Depending on the bank, manual approval might still be required.
} catch (EpsException $e) {
    echo 'EPS Exception: ' . $e->getMessage();
} catch (\Exception $e) {
    echo 'Exception: ' . $e->getMessage();
}
<?php
declare(strict_types=1);
require_once('../vendor/autoload.php');

use Psa\EpsBankTransfer\Api\SoCommunicator;
use Psa\EpsBankTransfer\Domain\BankConfirmationDetails;
use Psa\EpsBankTransfer\Domain\VitalityCheckDetails;
use Nyholm\Psr7\Factory\Psr17Factory;
use Symfony\Component\HttpClient\Psr18Client;

// This endpoint is called by the EPS Scheme Operator (SO):
// - First as a "vitality check" (pre-check) to verify your endpoint is reachable.
// - Later with the actual payment confirmation after the customer completes authorization at the bank.

$paymentConfirmationCallback = function (string $plainXml, BankConfirmationDetails $bankConfirmationDetails) {
    // Handle eps:StatusCode: 'OK', 'NOK', 'VOK' (validation OK), or 'UNKNOWN'
    if ($bankConfirmationDetails->getStatusCode() === 'OK') {
        // TODO: Mark the order as paid and proceed with fulfillment.
        // Prefer using a stable identifier you provided, e.g.:
        // - $bankConfirmationDetails->getRemittanceIdentifier()
        // - $bankConfirmationDetails->getUnstructuredRemittanceIdentifier()
        // Optionally log/inspect $plainXml for audits.
    }

    // Return true to acknowledge you received and processed the confirmation.
    // Returning false (or throwing) will signal to the SO that the confirmation was not accepted.
    return true;
};

$vitalityCheckCallback = function (string $plainXml, VitalityCheckDetails $vitalityCheckDetails) {
    // Return true to indicate your endpoint is healthy and reachable
    return true;
};

try {
    $psr17Factory = new Psr17Factory();
    $soCommunicator = new SoCommunicator(
        new Psr18Client(),
        $psr17Factory,
        $psr17Factory,
        SoCommunicator::TEST_MODE_URL, // Use LIVE base URL in production
        new Monolog\Logger('eps')
    );

    // Provide raw input/output streams. In production, keep these as shown.
    $soCommunicator->handleConfirmationUrl(
        $paymentConfirmationCallback,
        $vitalityCheckCallback,       // Vitality check callback
        'php://input',   // Raw POST body received from the SO
        'php://output'    // Raw output stream returned to the SO
    );
} catch (\Exception $e) {
    // Log and return a generic server error
    error_log($e->getMessage());
    http_response_code(500);
}
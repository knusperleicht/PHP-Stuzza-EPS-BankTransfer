<?php
declare(strict_types=1);
require_once('../vendor/autoload.php');

// Optional: Specify EPS interface version for all calls. Default is '2.6'.
// You can omit passing this constant to use the default.
const EPS_INTERFACE_VERSION = '2.6';

use Psa\EpsBankTransfer\Api\SoCommunicator;
use Psa\EpsBankTransfer\Requests\Parts\WebshopArticle;
use Psa\EpsBankTransfer\Requests\TransferInitiatorDetails;
use Psa\EpsBankTransfer\Requests\Parts\PaymentFlowUrls;
use Nyholm\Psr7\Factory\Psr17Factory;
use Symfony\Component\HttpClient\Psr18Client;

// === Configure your merchant and beneficiary account details ===
// Note: Replace all placeholders with your real data.
// For testing, keep using the TEST endpoint (see SoCommunicator below).
$userID = 'AKLJS231534';            // EPS merchant (User) ID = epsp:UserId
$pin    = 'topSecret';              // Merchant PIN/secret used for authentication (part of epsp:MD5Fingerprint)
$bic    = 'GAWIATW1XXX';            // BIC of the beneficiary account = epi:BfiBicIdentifier
$iban   = 'AT611904300234573201';   // IBAN of the beneficiary account = epi:BeneficiaryAccountIdentifier

// === URLs used during the payment flow ===
$paymentFlowUrls = new PaymentFlowUrls(
    // Confirmation URL: Called by the EPS Scheme Operator (SO) BEFORE (vitality check) and AFTER the customer authorizes payment.
    // IMPORTANT: Include a unique identifier in the query string (e.g., order ID) so you can match the callback to your order.
    // See samples/eps_confirm.php for handling.
    'https://yourdomain.example.com/eps_confirm.php?id=12345', // = epsp:ConfirmationUrl

    // Customer redirect on successful payment authorization.
    'https://yourdomain.example.com/ThankYou.html',            // = epsp:TransactionOkUrl

    // Customer redirect on cancellation or failure.
    'https://yourdomain.example.com/Failure.html'              // = epsp:TransactionNokUrl
);

$initiateTransferRequest = new TransferInitiatorDetails(
    $userID,
    $pin,
    $bic,
    // Beneficiary name (and optional address). Spec allows up to 140 chars, but banks often only guarantee 70. No line breaks.
    'John Q. Public',                                            // = epi:BeneficiaryNameAddressText
    $iban,
    // Reference identifier (mandatory by spec) but not returned in confirmation or shown on bank statement.
    // Best practice: reuse your order number (same as epi:RemittanceIdentifier).
    '12345',                                                     // = epi:ReferenceIdentifier
    // Total amount in EURO cents (e.g., 9999 = €99.99).
    '9999',                                                      // ≈ epi:InstructedAmount
    $paymentFlowUrls,
    null,
    60 // Optional: link timeout in minutes
);

// Optional: Include ONE (not both!) of the following remittance fields.
// These values are returned in the confirmation and are useful for matching.
$initiateTransferRequest->setRemittanceIdentifier('Order123');                  // "Zahlungsreferenz" = epi:RemittanceIdentifier
$initiateTransferRequest->setUnstructuredRemittanceIdentifier('Order123');     // "Verwendungszweck" = epi:UnstructuredRemittanceIdentifier

// Optional: Provide article details shown by some banks/webshops = epsp:WebshopDetails
$article = new WebshopArticle( // = epsp:WebshopArticle
    'ArticleName',   // Article name
    1,               // Quantity
    9999             // Unit price in EURO cents
);
$initiateTransferRequest->addArticle($article);

// === Send TransferInitiatorDetails to the EPS Scheme Operator (SO) ===
$testMode = true; // To use live mode, construct SoCommunicator with the LIVE base URL
$psr17Factory = new Psr17Factory();
$soCommunicator = new SoCommunicator(
    new Psr18Client(),
    $psr17Factory,
    $psr17Factory,
    SoCommunicator::TEST_MODE_URL, // Change to LIVE base URL for production
    new Monolog\Logger('eps')
);

// Optional: Display a bank selection to the user on your checkout page
// $bankList = $soCommunicator->GetBanksArray(); // or $soCommunicator->TryGetBanksArray();

// Optional: Override the default base URL (rarely needed)
// $soCommunicator->BaseUrl = 'https://example.com/My/Eps/Environment';

// Perform the initiate-transfer call and handle the response
try {
    // Optional version can be passed as the 2nd argument; default is '2.6'
    $protocolDetails = $soCommunicator->sendTransferInitiatorDetails($initiateTransferRequest, EPS_INTERFACE_VERSION);

    if ($protocolDetails->getErrorCode() !== '000') {
        // Non-success from SO: log/display for troubleshooting
        $errorCode = $protocolDetails->getErrorCode();
        $errorMessage = $protocolDetails->getErrorMessage();

        echo "Error occurred during EPS bank transfer initiation:\n";
        echo "Error code: " . $errorCode . "\n";
        echo "Error message: " . $errorMessage . "\n";
    } else {
        // Redirect the customer to their bank to complete authorization
        header('Location: ' . $protocolDetails->getClientRedirectUrl());
    }
} catch (\Exception $e) {
    // Handle unexpected exceptions
    $errorCode = 'Exception';
    $errorMessage = $e->getMessage();
}
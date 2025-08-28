<?php
require_once('../vendor/autoload.php');

use Externet\EpsBankTransfer;
use Externet\EpsBankTransfer\Api\V26\SoV26Communicator;
use Externet\EpsBankTransfer\Requests\InitiateTransferRequest;
use Externet\EpsBankTransfer\Requests\Parts\PaymentFlowUrls;
use Nyholm\Psr7\Factory\Psr17Factory;
use Symfony\Component\HttpClient\Psr18Client;

// Connection credentials. Override them for test mode.
$userID = 'AKLJS231534';            // Eps "Händler-ID"/UserID = epsp:UserId
$pin    = 'topSecret';              // Secret for authentication / PIN = part of epsp:MD5Fingerprint
$bic    = 'GAWIATW1XXX';            // BIC code of receiving bank account = epi:BfiBicIdentifier
$iban   = 'AT611904300234573201';   // IBAN code of receiving bank account = epi:BeneficiaryAccountIdentifier

// Return urls 
$paymentFlowUrls = new PaymentFlowUrls(
    'https://yourdomain.example.com/eps_confirm.php?id=12345', // The URL that the EPS Scheme Operator (=SO) will call before (= VitaliyCheck) and after payment = epsp:ConfirmationUrl. Use samples/eps_confirm.php as a starting point. You must include a unique query string (and parse it in samples/eps_confirm.php), since the matching of a confirmation to a payment is solely based on this URL!
    'https://yourdomain.example.com/ThankYou.html',   // The URL that the buyer will be redirected to on succesful payment = epsp:TransactionOkUrl
    'https://yourdomain.example.com/Failure.html'     // The URL that the buyer will be redirected to on cancel or failure = epsp:TransactionNokUrl
);

$initiateTransferRequest = new InitiateTransferRequest(
    $userID,
    $pin,
    $bic,
    'John Q. Public',         // Name (and optional address) of the receiving account owner = epi:BeneficiaryNameAddressText. In theory, this can be 140 characters; but in practice, Austrian banks only guarantee 70 characters. Line breaks are not allowed (EPS-Pflichtenheft is ambiguous about this).
    $iban,
    '12345',                  // epi:ReferenceIdentifier. Mandatory but useless, since you will never (!) get to see this number again - not upon payment confirmation and not at the bank statement (Kontoauszug). It's also not displayed to the customer. Best guess: Use your order number, i.e. same as epi:RemittanceIdentifier.
    '9999',                   // Total amount in EUR cent ≈ epi:InstructedAmount
    $paymentFlowUrls
);

// Optional: Include ONE (i.e. not both!) of the following two lines:
$initiateTransferRequest->setRemittanceIdentifier('Order123');                       // "Zahlungsreferenz". Will be returned on payment confirmation = epi:RemittanceIdentifier
$initiateTransferRequest->setUnstructuredRemittanceIdentifier('Order123'); // "Verwendungszweck". Will be returned on payment confirmation = epi:UnstructuredRemittanceIdentifier

// Optional:
$initiateTransferRequest->setExpirationMinutes(60);     // Sets ExpirationTimeout. Value must be between 5 and 60

// Optional: Include information about one or more articles = epsp:WebshopDetails
$article = new EpsBankTransfer\Requests\Parts\WebshopArticle(  // = epsp:WebshopArticle
    'ArticleName',  // Article name
    1,              // Quantity
    9999            // Price in EUR cents
);
$initiateTransferRequest->addArticle($article);

// Send TransferInitiatorDetails to Scheme Operator 
$testMode = true; // To use live mode call the SoCommunicator constructor with $testMode = false
$psr17Factory = new Psr17Factory();
$soCommunicator = new SoV26Communicator(
    new Psr18Client(),
    $psr17Factory,
    $psr17Factory,
    EpsBankTransfer\Api\AbstractSoCommunicator::TEST_MODE_URL,
    new Monolog\Logger('eps')
);
// Optional: You can provide a bank selection on your payment site
// $bankList = $soCommunicator->GetBanksArray(); // Alternative: TryGetBanksArray

// Optional: You can override the default URLs for test and live mode and specify your custom base URL
// $soCommunicator->BaseUrl = 'http://examplel.com/My/Eps/Test/Environment';

// Send transfer initiator details to default URL
try {
    $protocolDetails = $soCommunicator->initiateTransferRequest($initiateTransferRequest);

    if ($protocolDetails->getBankResponseDetails()->getErrorDetails()->getErrorCode() !== '000') {
        $errorCode = $protocolDetails->getBankResponseDetails()->getErrorDetails()->getErrorCode();
        $errorMsg = $protocolDetails->getBankResponseDetails()->getErrorDetails()->getErrorMsg();

        echo "Error occurred during EPS bank transfer initiation:\n";
        echo "Error code: " . $errorCode . "\n";
        echo "Error message: " . $errorMsg . "\n";

    } else {
        header('Location: ' . $protocolDetails->getBankResponseDetails()->getClientRedirectUrl());
    }
} catch (\Exception $e) {
    $errorCode = 'Exception';
    $errorMsg = $e->getMessage();
}
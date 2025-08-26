<?php
/**
 * Required packages:
 * composer require nyholm/psr7 symfony/http-client
 *
 * Note: Code is working with any PSR-17 compatible HTTP client and factory
 * like Guzzle, Symfony HTTP Client, etc.
 * Sample uses nyholm/psr7 + symfony/http-client
 */
require_once('../vendor/autoload.php');

use Externet\EpsBankTransfer;
use Externet\EpsBankTransfer\Api\SoCommunicator;
use Externet\EpsBankTransfer\TransferInitiatorDetails;
use Externet\EpsBankTransfer\TransferMsgDetails;
use Externet\EpsBankTransfer\Utilities\Constants;
use Externet\EpsBankTransfer\Utilities\XmlValidator;
use Nyholm\Psr7\Factory\Psr17Factory;
use Symfony\Component\HttpClient\Psr18Client;

// Connection credentials. Override them for test mode.
$userID = 'AKLJS231534';            // Eps "Händler-ID"/UserID = epsp:UserId
$pin    = 'topSecret';              // Secret for authentication / PIN = part of epsp:MD5Fingerprint
$bic    = 'GAWIATW1XXX';            // BIC code of receiving bank account = epi:BfiBicIdentifier
$iban   = 'AT611904300234573201';   // IBAN code of receiving bank account = epi:BeneficiaryAccountIdentifier

// Return urls 
$transferMsgDetails = new TransferMsgDetails(
    'https://yourdomain.example.com/eps_confirm.php?id=12345', // The URL that the EPS Scheme Operator (=SO) will call before (= VitaliyCheck) and after payment = epsp:ConfirmationUrl. Use samples/eps_confirm.php as a starting point. You must include a unique query string (and parse it in samples/eps_confirm.php), since the matching of a confirmation to a payment is solely based on this URL!
    'https://yourdomain.example.com/ThankYou.html',   // The URL that the buyer will be redirected to on succesful payment = epsp:TransactionOkUrl
    'https://yourdomain.example.com/Failure.html'     // The URL that the buyer will be redirected to on cancel or failure = epsp:TransactionNokUrl
);

$transferInitiatorDetails = new TransferInitiatorDetails(
    $userID,
    $pin,
    $bic,
    'John Q. Public',         // Name (and optional address) of the receiving account owner = epi:BeneficiaryNameAddressText. In theory, this can be 140 characters; but in practice, Austrian banks only guarantee 70 characters. Line breaks are not allowed (EPS-Pflichtenheft is ambiguous about this).
    $iban,
    '12345',                  // epi:ReferenceIdentifier. Mandatory but useless, since you will never (!) get to see this number again - not upon payment confirmation and not at the bank statement (Kontoauszug). It's also not displayed to the customer. Best guess: Use your order number, i.e. same as epi:RemittanceIdentifier.
    '9999',                   // Total amount in EUR cent ≈ epi:InstructedAmount
    $transferMsgDetails
);

// Optional: Include ONE (i.e. not both!) of the following two lines:
$transferInitiatorDetails->remittanceIdentifier = 'Order123';             // "Zahlungsreferenz". Will be returned on payment confirmation = epi:RemittanceIdentifier
$transferInitiatorDetails->unstructuredRemittanceIdentifier = 'Order123'; // "Verwendungszweck". Will be returned on payment confirmation = epi:UnstructuredRemittanceIdentifier

// Optional:
$transferInitiatorDetails->setExpirationMinutes(60);     // Sets ExpirationTimeout. Value must be between 5 and 60

// Optional: Include information about one or more articles = epsp:WebshopDetails
$article = new EpsBankTransfer\WebshopArticle(  // = epsp:WebshopArticle
    'ArticleName',  // Article name
    1,              // Quantity
    9999            // Price in EUR cents
);
$transferInitiatorDetails->webshopArticles[] = $article;

// Send TransferInitiatorDetails to Scheme Operator 
$testMode = true; // To use live mode call the SoCommunicator constructor with $testMode = false
$psr17Factory = new Psr17Factory();
$soCommunicator = new SoCommunicator(
    new Psr18Client(),
    $psr17Factory,
    $psr17Factory,
    SoCommunicator::TEST_MODE_URL
);
// Optional: You can provide a bank selection on your payment site
// $bankList = $soCommunicator->GetBanksArray(); // Alternative: TryGetBanksArray

// Optional: You can override the default URLs for test and live mode and specify your custom base URL
// $soCommunicator->BaseUrl = 'http://examplel.com/My/Eps/Test/Environment';

// Send transfer initiator details to default URL
try {
    $protocolDetails = $soCommunicator->SendTransferInitiatorDetails($transferInitiatorDetails);

    if ($protocolDetails->errorCode !== '000') {
        $errorCode = $protocolDetails->errorCode;
        $errorMsg = $protocolDetails->errorMsg;
    } else {
        header('Location: ' . $protocolDetails->clientRedirectUrl);
    }
} catch (\Exception $e) {
    $errorCode = 'Exception';
    $errorMsg = $e->getMessage();
}
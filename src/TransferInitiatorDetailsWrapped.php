<?php
declare(strict_types=1);

namespace Externet\EpsBankTransfer;

use DateTime;
use Exception;
use Externet\EpsBankTransfer\Generated\Protocol\V26\EpsProtocolDetails;
use Externet\EpsBankTransfer\Utilities\MoneyFormatter;
use InvalidArgumentException;

/**
 * EPS payment order message
 */
class TransferInitiatorDetailsWrapped
{

    /**
     * Business partner identification through UserID (= Merchant ID) issued by an eps bank
     * @var string
     */
    public $userId;

    /**
     * Secret given by bank
     * @var string
     */
    public $secret;

    /**
     * Creation date of payment order (xsd::date)
     * @var string
     */
    public $date;

    /**
     * ISO 9362 Bank Identifier Code (BIC) for bank identification
     * @var string
     */
    public $bfiBicIdentifier;

    /**
     * Identification of beneficiary (name and address) in unstructured form. Beneficiary does not need to match account holder.
     * @var string
     */
    public $beneficiaryNameAddressText;

    /**
     * Beneficiary's account details specified by IBAN (International Bank Account Number), e.g. AT611904300234573201 (11-digit account number: 00234573201)
     * @var string
     */
    public $beneficiaryAccountIdentifier;

    /**
     * Payment order message reference, e.g., for merchant research purposes
     * @var string
     */
    public $referenceIdentifier;

    /**
     *
     * @var string
     */
    public $unstructuredRemittanceIdentifier;

    /**
     * Unique merchant reference (= beneficiary) for a business transaction that must be returned unchanged to the merchant in payment transactions
     * @var string
     */
    public $remittanceIdentifier;

    /**
     * Min/max execution time for eps payment
     * @var string
     */
    public $expirationTime;

    /**
     * For cent values, they must be transmitted separated from the euro amount by a period, e.g. 150.55 (NOT 150,55)!
     * @var string
     */
    public $instructedAmount;

    /**
     * Currency specification according to ISO 4217
     * @var string
     */
    public $amountCurrencyIdentifier = 'EUR';

    /**
     * Array of webshop articles
     * @var WebshopArticle[]
     */
    public $webshopArticles;

    /**
     * Merchant specification of relevant URL addresses
     * @var TransferMsgDetails
     */
    public $transferMsgDetails;

    /**
     * Optional specification of bank details/BIC of the payment obligor / buyer
     * @var string
     */
    public $orderingCustomerOfiIdentifier;

    /**
     * @param string $userId
     * @param string $secret
     * @param string $bfiBicIdentifier
     * @param string $beneficiaryNameAddressText
     * @param string $beneficiaryAccountIdentifier
     * @param string $referenceIdentifier
     * @param int $instructedAmount in cents
     * @param TransferMsgDetails $transferMsgDetails
     * @param string|null $date
     */
    public function __construct(string $userId, string $secret, string $bfiBicIdentifier, string $beneficiaryNameAddressText, string $beneficiaryAccountIdentifier, string $referenceIdentifier, int $instructedAmount, TransferMsgDetails $transferMsgDetails, string $date = null)
    {
        $this->userId = $userId;
        $this->secret = $secret;
        $this->bfiBicIdentifier = $bfiBicIdentifier;
        $this->beneficiaryNameAddressText = $beneficiaryNameAddressText;
        $this->beneficiaryAccountIdentifier = $beneficiaryAccountIdentifier;
        $this->referenceIdentifier = $referenceIdentifier;
        $this->setInstructedAmount($instructedAmount);
        $this->webshopArticles = array();
        $this->transferMsgDetails = $transferMsgDetails;

        $this->date = $date == null ? date("Y-m-d") : $date;
    }

    /**
     * Sets ExpirationTime by adding a given number of minutes to the current
     * timestamp.
     * @param int $minutes Must be between 5 and 60
     * @throws InvalidArgumentException|Exception if minutes not between 5 and 60
     */
    public function setExpirationMinutes(int $minutes)
    {
        if ($minutes < 5 || $minutes > 60)
            throw new InvalidArgumentException('Expiration minutes value of "' . $minutes . '" is not between 5 and 60.');

        $expires = new DateTime();
        $expires->add(new \DateInterval('PT' . $minutes . 'M'));
        $this->expirationTime = $expires->format(DATE_RFC3339);
    }

    /**
     *
     * @param int $amount in cents
     */
    public function setInstructedAmount(int $amount)
    {
        $this->instructedAmount = MoneyFormatter::formatXsdDecimal($amount);
    }

    public function getMD5Fingerprint(): string
    {
        $remittanceIdentifier = $this->unstructuredRemittanceIdentifier ?: $this->remittanceIdentifier;

        $input = $this->secret . $this->date . $this->referenceIdentifier . $this->beneficiaryAccountIdentifier
            . $remittanceIdentifier . $this->instructedAmount . $this->amountCurrencyIdentifier
            . $this->userId;

        return md5($input);
    }

    /**
     * @throws Exception
     */
    public function getSimpleXml(): EpsProtocolDetails
    {
        $xml = new Generated\Protocol\V26\EpsProtocolDetails();
        $xml->setSessionLanguage("DE");

        $transferInitiatorDetails = new Generated\Protocol\V26\TransferInitiatorDetails();
        $xml->setTransferInitiatorDetails($transferInitiatorDetails);

        $paymentInitiatorDetails = new Generated\Payment\V26\PaymentInitiatorDetails();
        $transferInitiatorDetails->setPaymentInitiatorDetails($paymentInitiatorDetails);

        $transferMsgDetails = new Generated\Protocol\V26\TransferMsgDetails();
        $transferMsgDetails->setConfirmationUrl($this->transferMsgDetails->getConfirmationUrl());

        $x = new Generated\Protocol\V26\TransactionOkUrl($this->transferMsgDetails->getTransactionOkUrl());

//        if (!empty($this->transferMsgDetails->getTargetWindowOk())) {
//            $x->setTargetWindow($this->transferMsgDetails->getTargetWindowOk());
//        }

        $transferMsgDetails->setTransactionOkUrl($x);


        $y = new Generated\Protocol\V26\TransactionNokUrl($this->transferMsgDetails->getTransactionNokUrl());

//        if (!empty($this->transferMsgDetails->getTargetWindowNok())) {
//            $y->setTargetWindow($this->transferMsgDetails->getTargetWindowNok());
//        }

        $transferMsgDetails->setTransactionNokUrl($y);


        $transferInitiatorDetails->setTransferMsgDetails($transferMsgDetails);

        if (!empty($this->webshopArticles)) {
            $articles = [];
            foreach ($this->webshopArticles as $article) {
                $webshopArticle = new Generated\Protocol\V26\WebshopArticle();
                $webshopArticle->setArticleName($article->name);
                $webshopArticle->setArticleCount($article->count);
                $webshopArticle->setArticlePrice($article->price);

                $articles[] = $webshopArticle;
            }
            $transferInitiatorDetails->setWebshopDetails($articles);
        }

        $authenticationDetails = new Generated\Protocol\V26\AuthenticationDetails();
        $authenticationDetails->setUserId($this->userId);
        $authenticationDetails->setMD5Fingerprint($this->getMD5Fingerprint());
        $transferInitiatorDetails->setAuthenticationDetails($authenticationDetails);

        $epiDetails = new Generated\Epi\EpiDetails();
        $identificationDetails = new Generated\Epi\IdentificationDetails();
        $partyDetails = new Generated\Epi\PartyDetails();
        $paymentInstructionDetails = new Generated\Epi\PaymentInstructionDetails();

        if ($this->unstructuredRemittanceIdentifier == null) {
            $paymentInstructionDetails->setRemittanceIdentifier($this->remittanceIdentifier);
        } else {
            $paymentInstructionDetails->setUnstructuredRemittanceIdentifier($this->unstructuredRemittanceIdentifier);
        }

        $instructedAmount = new Generated\Epi\InstructedAmount($this->instructedAmount);
        $instructedAmount->setAmountCurrencyIdentifier($this->amountCurrencyIdentifier);
        $paymentInstructionDetails->setInstructedAmount($instructedAmount);
        $paymentInstructionDetails->setChargeCode('SHA');

        $bfiPartyDetails = new Generated\Epi\BfiPartyDetails();
        $bfiPartyDetails->setBfiBicIdentifier($this->bfiBicIdentifier);
        $partyDetails->setBfiPartyDetails($bfiPartyDetails);

        $beneficiaryPartyDetails = new Generated\Epi\BeneficiaryPartyDetails();
        $beneficiaryPartyDetails->setBeneficiaryNameAddressText($this->beneficiaryNameAddressText);
        $beneficiaryPartyDetails->setBeneficiaryAccountIdentifier($this->beneficiaryAccountIdentifier);
        $partyDetails->setBeneficiaryPartyDetails($beneficiaryPartyDetails);

        $identificationDetails->setDate($this->date);
        $identificationDetails->setReferenceIdentifier($this->referenceIdentifier);

        if (!empty($this->orderingCustomerOfiIdentifier)) {
            $identificationDetails->setOrderingCustomerOfiIdentifier($this->orderingCustomerOfiIdentifier);
        }

        $epiDetails->setIdentificationDetails($identificationDetails);
        $epiDetails->setPartyDetails($partyDetails);
        $epiDetails->setPaymentInstructionDetails($paymentInstructionDetails);

        $paymentInitiatorDetails->setEpiDetails($epiDetails);

        $austrianRulesDetails = new Generated\AustrianRules\AustrianRulesDetails();
        $austrianRulesDetails->setDigSig('SIG');

        if (!empty($this->expirationTime)) {
            $austrianRulesDetails->setExpirationTime(new DateTime($this->expirationTime));
        }

        $paymentInitiatorDetails->setAustrianRulesDetails($austrianRulesDetails);

        return $xml;
    }
}
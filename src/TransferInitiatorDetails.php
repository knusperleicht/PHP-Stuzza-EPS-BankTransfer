<?php
declare(strict_types=1);

namespace Externet\EpsBankTransfer;

use DateTime;
use Externet\EpsBankTransfer\Utilities\MoneyFormatter;
use InvalidArgumentException;

/**
 * EPS payment order message
 */
class TransferInitiatorDetails
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
     * Payment order message reference, e.g. for merchant research purposes
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
     * Sets ExpirationTime by adding given number of minutes to the current
     * timestamp.
     * @param int $minutes Must be between 5 and 60
     * @throws InvalidArgumentException|\Exception if minutes not between 5 and 60
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
     * @throws \Exception
     */
    public function getSimpleXml(): EpsXmlElement
    {
        $xml = EpsXmlElement::createEmptySimpleXml('epsp:EpsProtocolDetails SessionLanguage="DE" xsi:schemaLocation="http://www.stuzza.at/namespaces/eps/protocol/2014/10 EPSProtocol-V26.xsd" xmlns:atrul="http://www.stuzza.at/namespaces/eps/austrianrules/2014/10" xmlns:epi="http://www.stuzza.at/namespaces/eps/epi/2013/02" xmlns:eps="http://www.stuzza.at/namespaces/eps/payment/2014/10" xmlns:epsp="http://www.stuzza.at/namespaces/eps/protocol/2014/10" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"');

        $TransferInitiatorDetails = $xml->addChildExt('TransferInitiatorDetails', '', 'epsp');

        $PaymentInitiatorDetails = $TransferInitiatorDetails->addChildExt('PaymentInitiatorDetails', '', 'eps');
        $TransferMsgDetails = $TransferInitiatorDetails->addChildExt('TransferMsgDetails', '', 'epsp');
        $TransferMsgDetails->addChildExt('ConfirmationUrl', $this->transferMsgDetails->confirmationUrl, 'epsp');
        $TransactionOkUrl = $TransferMsgDetails->addChildExt('TransactionOkUrl', $this->transferMsgDetails->transactionOkUrl, 'epsp');
        $TransactionNokUrl = $TransferMsgDetails->addChildExt('TransactionNokUrl', $this->transferMsgDetails->transactionNokUrl, 'epsp');

        if (!empty($this->transferMsgDetails->TargetWindowOk))
            $TransactionOkUrl->addAttribute('TargetWindow', $this->transferMsgDetails->TargetWindowOk);

        if (!empty($this->transferMsgDetails->TargetWindowNok))
            $TransactionNokUrl->addAttribute('TargetWindow', $this->transferMsgDetails->TargetWindowNok);

        if (!empty($this->webshopArticles)) {
            $WebshopDetails = $TransferInitiatorDetails->addChildExt('WebshopDetails', '', 'epsp');

            foreach ($this->webshopArticles as $article) {
                $WebshopArticle = $WebshopDetails->addChildExt('WebshopArticle', '', 'epsp');
                $WebshopArticle->addAttribute('ArticleName', $article->Name);
                $WebshopArticle->addAttribute('ArticleCount', $article->Count);
                $WebshopArticle->addAttribute('ArticlePrice', $article->Price);
            }
        }

        $AuthenticationDetails = $TransferInitiatorDetails->addChildExt('AuthenticationDetails', '', 'epsp');
        $AuthenticationDetails->addChildExt('UserId', $this->userId, 'epsp');
        $AuthenticationDetails->addChildExt('MD5Fingerprint', $this->getMD5Fingerprint(), 'epsp');

        $EpiDetails = $PaymentInitiatorDetails->addChildExt('EpiDetails', '', 'epi');
        $IdentificationDetails = $EpiDetails->addChildExt("IdentificationDetails", '', 'epi');
        $PartyDetails = $EpiDetails->addChildExt('PartyDetails', '', 'epi');
        $PaymentInstructionDetails = $EpiDetails->addChildExt('PaymentInstructionDetails', '', 'epi');
        if ($this->unstructuredRemittanceIdentifier == null)
            $PaymentInstructionDetails->addChildExt('RemittanceIdentifier', $this->remittanceIdentifier, 'epi');
        else
            $PaymentInstructionDetails->addChildExt('UnstructuredRemittanceIdentifier', $this->unstructuredRemittanceIdentifier, 'epi');
        $InstructedAmount = $PaymentInstructionDetails->addChildExt('InstructedAmount', $this->instructedAmount, 'epi');
        $InstructedAmount->addAttribute('AmountCurrencyIdentifier', $this->amountCurrencyIdentifier);
        $PaymentInstructionDetails->addChildExt('ChargeCode', 'SHA', 'epi');

        $BfiPartyDetails = $PartyDetails->addChildExt('BfiPartyDetails', '', 'epi');
        $BfiPartyDetails->addChildExt('BfiBicIdentifier', $this->bfiBicIdentifier, 'epi');
        $BeneficiaryPartyDetails = $PartyDetails->addChildExt('BeneficiaryPartyDetails', '', 'epi');
        $BeneficiaryPartyDetails->addChildExt('BeneficiaryNameAddressText', $this->beneficiaryNameAddressText, 'epi');
        $BeneficiaryPartyDetails->addChildExt('BeneficiaryAccountIdentifier', $this->beneficiaryAccountIdentifier, 'epi');
        $IdentificationDetails->addChildExt('Date', $this->date, 'epi');
        $IdentificationDetails->addChildExt('ReferenceIdentifier', $this->referenceIdentifier, 'epi');

        if (!empty($this->orderingCustomerOfiIdentifier))
            $IdentificationDetails->addChildExt('OrderingCustomerOfiIdentifier', $this->orderingCustomerOfiIdentifier, 'epi');

        $AustrianRulesDetails = $PaymentInitiatorDetails->addChildExt('AustrianRulesDetails', '', 'atrul');
        $AustrianRulesDetails->addChildExt('DigSig', 'SIG', 'atrul');

        if (!empty($this->expirationTime))
            $AustrianRulesDetails->addChildExt('ExpirationTime', $this->expirationTime, 'atrul');

        return $xml;
    }
}
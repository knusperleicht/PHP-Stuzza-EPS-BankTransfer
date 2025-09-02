<?php
declare(strict_types=1);

namespace Knusperleicht\EpsBankTransfer\Requests\Mappers;

use DateTime;
use Exception;
use Knusperleicht\EpsBankTransfer\Internal\Generated\AustrianRules\AustrianRulesDetails;
use Knusperleicht\EpsBankTransfer\Internal\Generated\Epi\BeneficiaryPartyDetails;
use Knusperleicht\EpsBankTransfer\Internal\Generated\Epi\BfiPartyDetails;
use Knusperleicht\EpsBankTransfer\Internal\Generated\Epi\EpiDetails;
use Knusperleicht\EpsBankTransfer\Internal\Generated\Epi\IdentificationDetails;
use Knusperleicht\EpsBankTransfer\Internal\Generated\Epi\InstructedAmount;
use Knusperleicht\EpsBankTransfer\Internal\Generated\Epi\PartyDetails;
use Knusperleicht\EpsBankTransfer\Internal\Generated\Epi\PaymentInstructionDetails;
use Knusperleicht\EpsBankTransfer\Internal\Generated\Payment\V27\PaymentInitiatorDetails as PaymentInitiatorDetailsV27;
use Knusperleicht\EpsBankTransfer\Internal\Generated\Protocol\V27\AuthenticationDetails as AuthenticationDetailsV27;
use Knusperleicht\EpsBankTransfer\Internal\Generated\Protocol\V27\EpsProtocolDetails as EpsProtocolDetailsV27;
use Knusperleicht\EpsBankTransfer\Internal\Generated\Protocol\V27\TransactionNokUrl as TransactionNokUrlV27;
use Knusperleicht\EpsBankTransfer\Internal\Generated\Protocol\V27\TransactionOkUrl as TransactionOkUrlV27;
use Knusperleicht\EpsBankTransfer\Internal\Generated\Protocol\V27\TransferInitiatorDetails as TransferInitiatorDetailsV27;
use Knusperleicht\EpsBankTransfer\Internal\Generated\Protocol\V27\TransferMsgDetails as TransferMsgDetailsV27;
use Knusperleicht\EpsBankTransfer\Internal\Generated\Protocol\V27\WebshopArticle as WebshopArticleV27;
use Knusperleicht\EpsBankTransfer\Requests\TransferInitiatorDetails;

/**
 * Maps domain TransferInitiatorDetails to EPS v2.7 generated tree.
 */
class TransferInitiatorDetailsV27Mapper
{
    /**
     * @throws Exception
     */
    public static function map(TransferInitiatorDetails $src): EpsProtocolDetailsV27
    {
        $xml = new EpsProtocolDetailsV27();
        $xml->setSessionLanguage('DE');

        $transferInitiatorDetails = new TransferInitiatorDetailsV27();

        $paymentInitiatorDetails = new PaymentInitiatorDetailsV27();
        $transferInitiatorDetails->setPaymentInitiatorDetails($paymentInitiatorDetails);

        $transferMsgDetails = new TransferMsgDetailsV27();
        $transferMsgDetails->setConfirmationUrl($src->getTransferMsgDetails()->getConfirmationUrl());

        $transactionOkUrl = new TransactionOkUrlV27($src->getTransferMsgDetails()->getTransactionOkUrl());
        $transferMsgDetails->setTransactionOkUrl($transactionOkUrl);

        $transactionNokUrl = new TransactionNokUrlV27($src->getTransferMsgDetails()->getTransactionNokUrl());
        $transferMsgDetails->setTransactionNokUrl($transactionNokUrl);

        $transferInitiatorDetails->setTransferMsgDetails($transferMsgDetails);

        if (!empty($src->getWebshopArticles())) {
            $articles = [];
            foreach ($src->getWebshopArticles() as $article) {
                $webshopArticle = new WebshopArticleV27();
                $webshopArticle->setArticleName($article->name);
                $webshopArticle->setArticleCount($article->count);
                $webshopArticle->setArticlePrice($article->price);
                $articles[] = $webshopArticle;
            }
            $transferInitiatorDetails->setWebshopDetails($articles);
        }

        $authenticationDetails = new AuthenticationDetailsV27();
        $authenticationDetails->setUserId($src->getUserId());
        $authenticationDetails->setSHA256Fingerprint($src->getSha256Fingerprint());
        $transferInitiatorDetails->setAuthenticationDetails($authenticationDetails);

        $epiDetails = new EpiDetails();
        $identificationDetails = new IdentificationDetails();
        $partyDetails = new PartyDetails();
        $paymentInstructionDetails = new PaymentInstructionDetails();

        if ($src->getUnstructuredRemittanceIdentifier() === null) {
            $paymentInstructionDetails->setRemittanceIdentifier((string)$src->getRemittanceIdentifier());
        } else {
            $paymentInstructionDetails->setUnstructuredRemittanceIdentifier((string)$src->getUnstructuredRemittanceIdentifier());
        }

        $instructedAmount = new InstructedAmount($src->getInstructedAmount());
        $instructedAmount->setAmountCurrencyIdentifier($src->getAmountCurrencyIdentifier());
        $paymentInstructionDetails->setInstructedAmount($instructedAmount);
        $paymentInstructionDetails->setChargeCode('SHA');

        $bfiPartyDetails = new BfiPartyDetails();
        $bfiPartyDetails->setBfiBicIdentifier($src->getBfiBicIdentifier());
        $partyDetails->setBfiPartyDetails($bfiPartyDetails);

        $beneficiaryPartyDetails = new BeneficiaryPartyDetails();
        $beneficiaryPartyDetails->setBeneficiaryNameAddressText($src->getBeneficiaryNameAddressText());
        $beneficiaryPartyDetails->setBeneficiaryAccountIdentifier($src->getBeneficiaryAccountIdentifier());
        $partyDetails->setBeneficiaryPartyDetails($beneficiaryPartyDetails);

        $identificationDetails->setDate($src->getDate());
        $identificationDetails->setReferenceIdentifier($src->getReferenceIdentifier());

        if (!empty($src->getOrderingCustomerOfiIdentifier())) {
            $identificationDetails->setOrderingCustomerOfiIdentifier($src->getOrderingCustomerOfiIdentifier());
        }

        $epiDetails->setIdentificationDetails($identificationDetails);
        $epiDetails->setPartyDetails($partyDetails);
        $epiDetails->setPaymentInstructionDetails($paymentInstructionDetails);
        $paymentInitiatorDetails->setEpiDetails($epiDetails);

        $austrianRulesDetails = new AustrianRulesDetails();
        $austrianRulesDetails->setDigSig('SIG');

        if (!empty($src->getExpirationTime())) {
            $austrianRulesDetails->setExpirationTime(new DateTime($src->getExpirationTime()));
        }

        $paymentInitiatorDetails->setAustrianRulesDetails($austrianRulesDetails);

        $xml->setTransferInitiatorDetails($transferInitiatorDetails);
        return $xml;
    }
}

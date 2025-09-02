<?php

namespace Knusperleicht\EpsBankTransfer\Internal\Generated\Protocol\V26\EpsProtocolDetails;

/**
 * Class representing EpsProtocolDetailsAType
 */
class EpsProtocolDetailsAType
{
    /**
     * @var string $sessionLanguage
     */
    private $sessionLanguage = null;

    /**
     * @var \Knusperleicht\EpsBankTransfer\Internal\Generated\Protocol\V26\TransferInitiatorDetails $transferInitiatorDetails
     */
    private $transferInitiatorDetails = null;

    /**
     * @var \Knusperleicht\EpsBankTransfer\Internal\Generated\Protocol\V26\BankResponseDetails $bankResponseDetails
     */
    private $bankResponseDetails = null;

    /**
     * @var \Knusperleicht\EpsBankTransfer\Internal\Generated\Protocol\V26\VitalityCheckDetails $vitalityCheckDetails
     */
    private $vitalityCheckDetails = null;

    /**
     * @var \Knusperleicht\EpsBankTransfer\Internal\Generated\Protocol\V26\BankConfirmationDetails $bankConfirmationDetails
     */
    private $bankConfirmationDetails = null;

    /**
     * @var \Knusperleicht\EpsBankTransfer\Internal\Generated\Protocol\V26\ShopResponseDetails $shopResponseDetails
     */
    private $shopResponseDetails = null;

    /**
     * @var \Knusperleicht\EpsBankTransfer\Internal\Generated\Protocol\V26\TransactionDetailsRequest $transactionDetailsRequest
     */
    private $transactionDetailsRequest = null;

    /**
     * @var \Knusperleicht\EpsBankTransfer\Internal\Generated\Protocol\V26\TransactionDetailsResponse $transactionDetailsResponse
     */
    private $transactionDetailsResponse = null;

    /**
     * @var \Knusperleicht\EpsBankTransfer\Internal\Generated\Protocol\V26\ConfirmationStatusRequest $confirmationStatusRequest
     */
    private $confirmationStatusRequest = null;

    /**
     * @var \Knusperleicht\EpsBankTransfer\Internal\Generated\Protocol\V26\ConfirmationStatusResponse $confirmationStatusResponse
     */
    private $confirmationStatusResponse = null;

    /**
     * @var \Knusperleicht\EpsBankTransfer\Internal\Generated\Protocol\V26\StatusMsg $statusMsg
     */
    private $statusMsg = null;

    /**
     * Gets as sessionLanguage
     *
     * @return string
     */
    public function getSessionLanguage()
    {
        return $this->sessionLanguage;
    }

    /**
     * Sets a new sessionLanguage
     *
     * @param string $sessionLanguage
     * @return self
     */
    public function setSessionLanguage($sessionLanguage)
    {
        $this->sessionLanguage = $sessionLanguage;
        return $this;
    }

    /**
     * Gets as transferInitiatorDetails
     *
     * @return \Knusperleicht\EpsBankTransfer\Internal\Generated\Protocol\V26\TransferInitiatorDetails
     */
    public function getTransferInitiatorDetails()
    {
        return $this->transferInitiatorDetails;
    }

    /**
     * Sets a new transferInitiatorDetails
     *
     * @param \Knusperleicht\EpsBankTransfer\Internal\Generated\Protocol\V26\TransferInitiatorDetails $transferInitiatorDetails
     * @return self
     */
    public function setTransferInitiatorDetails(?\Knusperleicht\EpsBankTransfer\Internal\Generated\Protocol\V26\TransferInitiatorDetails $transferInitiatorDetails = null)
    {
        $this->transferInitiatorDetails = $transferInitiatorDetails;
        return $this;
    }

    /**
     * Gets as bankResponseDetails
     *
     * @return \Knusperleicht\EpsBankTransfer\Internal\Generated\Protocol\V26\BankResponseDetails
     */
    public function getBankResponseDetails()
    {
        return $this->bankResponseDetails;
    }

    /**
     * Sets a new bankResponseDetails
     *
     * @param \Knusperleicht\EpsBankTransfer\Internal\Generated\Protocol\V26\BankResponseDetails $bankResponseDetails
     * @return self
     */
    public function setBankResponseDetails(?\Knusperleicht\EpsBankTransfer\Internal\Generated\Protocol\V26\BankResponseDetails $bankResponseDetails = null)
    {
        $this->bankResponseDetails = $bankResponseDetails;
        return $this;
    }

    /**
     * Gets as vitalityCheckDetails
     *
     * @return \Knusperleicht\EpsBankTransfer\Internal\Generated\Protocol\V26\VitalityCheckDetails
     */
    public function getVitalityCheckDetails()
    {
        return $this->vitalityCheckDetails;
    }

    /**
     * Sets a new vitalityCheckDetails
     *
     * @param \Knusperleicht\EpsBankTransfer\Internal\Generated\Protocol\V26\VitalityCheckDetails $vitalityCheckDetails
     * @return self
     */
    public function setVitalityCheckDetails(?\Knusperleicht\EpsBankTransfer\Internal\Generated\Protocol\V26\VitalityCheckDetails $vitalityCheckDetails = null)
    {
        $this->vitalityCheckDetails = $vitalityCheckDetails;
        return $this;
    }

    /**
     * Gets as bankConfirmationDetails
     *
     * @return \Knusperleicht\EpsBankTransfer\Internal\Generated\Protocol\V26\BankConfirmationDetails
     */
    public function getBankConfirmationDetails()
    {
        return $this->bankConfirmationDetails;
    }

    /**
     * Sets a new bankConfirmationDetails
     *
     * @param \Knusperleicht\EpsBankTransfer\Internal\Generated\Protocol\V26\BankConfirmationDetails $bankConfirmationDetails
     * @return self
     */
    public function setBankConfirmationDetails(?\Knusperleicht\EpsBankTransfer\Internal\Generated\Protocol\V26\BankConfirmationDetails $bankConfirmationDetails = null)
    {
        $this->bankConfirmationDetails = $bankConfirmationDetails;
        return $this;
    }

    /**
     * Gets as shopResponseDetails
     *
     * @return \Knusperleicht\EpsBankTransfer\Internal\Generated\Protocol\V26\ShopResponseDetails
     */
    public function getShopResponseDetails()
    {
        return $this->shopResponseDetails;
    }

    /**
     * Sets a new shopResponseDetails
     *
     * @param \Knusperleicht\EpsBankTransfer\Internal\Generated\Protocol\V26\ShopResponseDetails $shopResponseDetails
     * @return self
     */
    public function setShopResponseDetails(?\Knusperleicht\EpsBankTransfer\Internal\Generated\Protocol\V26\ShopResponseDetails $shopResponseDetails = null)
    {
        $this->shopResponseDetails = $shopResponseDetails;
        return $this;
    }

    /**
     * Gets as transactionDetailsRequest
     *
     * @return \Knusperleicht\EpsBankTransfer\Internal\Generated\Protocol\V26\TransactionDetailsRequest
     */
    public function getTransactionDetailsRequest()
    {
        return $this->transactionDetailsRequest;
    }

    /**
     * Sets a new transactionDetailsRequest
     *
     * @param \Knusperleicht\EpsBankTransfer\Internal\Generated\Protocol\V26\TransactionDetailsRequest $transactionDetailsRequest
     * @return self
     */
    public function setTransactionDetailsRequest(?\Knusperleicht\EpsBankTransfer\Internal\Generated\Protocol\V26\TransactionDetailsRequest $transactionDetailsRequest = null)
    {
        $this->transactionDetailsRequest = $transactionDetailsRequest;
        return $this;
    }

    /**
     * Gets as transactionDetailsResponse
     *
     * @return \Knusperleicht\EpsBankTransfer\Internal\Generated\Protocol\V26\TransactionDetailsResponse
     */
    public function getTransactionDetailsResponse()
    {
        return $this->transactionDetailsResponse;
    }

    /**
     * Sets a new transactionDetailsResponse
     *
     * @param \Knusperleicht\EpsBankTransfer\Internal\Generated\Protocol\V26\TransactionDetailsResponse $transactionDetailsResponse
     * @return self
     */
    public function setTransactionDetailsResponse(?\Knusperleicht\EpsBankTransfer\Internal\Generated\Protocol\V26\TransactionDetailsResponse $transactionDetailsResponse = null)
    {
        $this->transactionDetailsResponse = $transactionDetailsResponse;
        return $this;
    }

    /**
     * Gets as confirmationStatusRequest
     *
     * @return \Knusperleicht\EpsBankTransfer\Internal\Generated\Protocol\V26\ConfirmationStatusRequest
     */
    public function getConfirmationStatusRequest()
    {
        return $this->confirmationStatusRequest;
    }

    /**
     * Sets a new confirmationStatusRequest
     *
     * @param \Knusperleicht\EpsBankTransfer\Internal\Generated\Protocol\V26\ConfirmationStatusRequest $confirmationStatusRequest
     * @return self
     */
    public function setConfirmationStatusRequest(?\Knusperleicht\EpsBankTransfer\Internal\Generated\Protocol\V26\ConfirmationStatusRequest $confirmationStatusRequest = null)
    {
        $this->confirmationStatusRequest = $confirmationStatusRequest;
        return $this;
    }

    /**
     * Gets as confirmationStatusResponse
     *
     * @return \Knusperleicht\EpsBankTransfer\Internal\Generated\Protocol\V26\ConfirmationStatusResponse
     */
    public function getConfirmationStatusResponse()
    {
        return $this->confirmationStatusResponse;
    }

    /**
     * Sets a new confirmationStatusResponse
     *
     * @param \Knusperleicht\EpsBankTransfer\Internal\Generated\Protocol\V26\ConfirmationStatusResponse $confirmationStatusResponse
     * @return self
     */
    public function setConfirmationStatusResponse(?\Knusperleicht\EpsBankTransfer\Internal\Generated\Protocol\V26\ConfirmationStatusResponse $confirmationStatusResponse = null)
    {
        $this->confirmationStatusResponse = $confirmationStatusResponse;
        return $this;
    }

    /**
     * Gets as statusMsg
     *
     * @return \Knusperleicht\EpsBankTransfer\Internal\Generated\Protocol\V26\StatusMsg
     */
    public function getStatusMsg()
    {
        return $this->statusMsg;
    }

    /**
     * Sets a new statusMsg
     *
     * @param \Knusperleicht\EpsBankTransfer\Internal\Generated\Protocol\V26\StatusMsg $statusMsg
     * @return self
     */
    public function setStatusMsg(?\Knusperleicht\EpsBankTransfer\Internal\Generated\Protocol\V26\StatusMsg $statusMsg = null)
    {
        $this->statusMsg = $statusMsg;
        return $this;
    }
}


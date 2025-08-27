<?php

namespace Externet\EpsBankTransfer\Generated\Protocol\V27\EpsProtocolDetails;

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
     * @var \Externet\EpsBankTransfer\Generated\Protocol\V27\TransferInitiatorDetails $transferInitiatorDetails
     */
    private $transferInitiatorDetails = null;

    /**
     * @var \Externet\EpsBankTransfer\Generated\Protocol\V27\BankResponseDetails $bankResponseDetails
     */
    private $bankResponseDetails = null;

    /**
     * @var \Externet\EpsBankTransfer\Generated\Protocol\V27\VitalityCheckDetails $vitalityCheckDetails
     */
    private $vitalityCheckDetails = null;

    /**
     * @var \Externet\EpsBankTransfer\Generated\Protocol\V27\BankConfirmationDetails $bankConfirmationDetails
     */
    private $bankConfirmationDetails = null;

    /**
     * @var \Externet\EpsBankTransfer\Generated\Protocol\V27\ShopResponseDetails $shopResponseDetails
     */
    private $shopResponseDetails = null;

    /**
     * @var \Externet\EpsBankTransfer\Generated\Protocol\V27\TransactionDetailsRequest $transactionDetailsRequest
     */
    private $transactionDetailsRequest = null;

    /**
     * @var \Externet\EpsBankTransfer\Generated\Protocol\V27\TransactionDetailsResponse $transactionDetailsResponse
     */
    private $transactionDetailsResponse = null;

    /**
     * @var \Externet\EpsBankTransfer\Generated\Protocol\V27\ConfirmationStatusRequest $confirmationStatusRequest
     */
    private $confirmationStatusRequest = null;

    /**
     * @var \Externet\EpsBankTransfer\Generated\Protocol\V27\ConfirmationStatusResponse $confirmationStatusResponse
     */
    private $confirmationStatusResponse = null;

    /**
     * @var \Externet\EpsBankTransfer\Generated\Protocol\V27\StatusMsg $statusMsg
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
     * @return \Externet\EpsBankTransfer\Generated\Protocol\V27\TransferInitiatorDetails
     */
    public function getTransferInitiatorDetails()
    {
        return $this->transferInitiatorDetails;
    }

    /**
     * Sets a new transferInitiatorDetails
     *
     * @param \Externet\EpsBankTransfer\Generated\Protocol\V27\TransferInitiatorDetails $transferInitiatorDetails
     * @return self
     */
    public function setTransferInitiatorDetails(?\Externet\EpsBankTransfer\Generated\Protocol\V27\TransferInitiatorDetails $transferInitiatorDetails = null)
    {
        $this->transferInitiatorDetails = $transferInitiatorDetails;
        return $this;
    }

    /**
     * Gets as bankResponseDetails
     *
     * @return \Externet\EpsBankTransfer\Generated\Protocol\V27\BankResponseDetails
     */
    public function getBankResponseDetails()
    {
        return $this->bankResponseDetails;
    }

    /**
     * Sets a new bankResponseDetails
     *
     * @param \Externet\EpsBankTransfer\Generated\Protocol\V27\BankResponseDetails $bankResponseDetails
     * @return self
     */
    public function setBankResponseDetails(?\Externet\EpsBankTransfer\Generated\Protocol\V27\BankResponseDetails $bankResponseDetails = null)
    {
        $this->bankResponseDetails = $bankResponseDetails;
        return $this;
    }

    /**
     * Gets as vitalityCheckDetails
     *
     * @return \Externet\EpsBankTransfer\Generated\Protocol\V27\VitalityCheckDetails
     */
    public function getVitalityCheckDetails()
    {
        return $this->vitalityCheckDetails;
    }

    /**
     * Sets a new vitalityCheckDetails
     *
     * @param \Externet\EpsBankTransfer\Generated\Protocol\V27\VitalityCheckDetails $vitalityCheckDetails
     * @return self
     */
    public function setVitalityCheckDetails(?\Externet\EpsBankTransfer\Generated\Protocol\V27\VitalityCheckDetails $vitalityCheckDetails = null)
    {
        $this->vitalityCheckDetails = $vitalityCheckDetails;
        return $this;
    }

    /**
     * Gets as bankConfirmationDetails
     *
     * @return \Externet\EpsBankTransfer\Generated\Protocol\V27\BankConfirmationDetails
     */
    public function getBankConfirmationDetails()
    {
        return $this->bankConfirmationDetails;
    }

    /**
     * Sets a new bankConfirmationDetails
     *
     * @param \Externet\EpsBankTransfer\Generated\Protocol\V27\BankConfirmationDetails $bankConfirmationDetails
     * @return self
     */
    public function setBankConfirmationDetails(?\Externet\EpsBankTransfer\Generated\Protocol\V27\BankConfirmationDetails $bankConfirmationDetails = null)
    {
        $this->bankConfirmationDetails = $bankConfirmationDetails;
        return $this;
    }

    /**
     * Gets as shopResponseDetails
     *
     * @return \Externet\EpsBankTransfer\Generated\Protocol\V27\ShopResponseDetails
     */
    public function getShopResponseDetails()
    {
        return $this->shopResponseDetails;
    }

    /**
     * Sets a new shopResponseDetails
     *
     * @param \Externet\EpsBankTransfer\Generated\Protocol\V27\ShopResponseDetails $shopResponseDetails
     * @return self
     */
    public function setShopResponseDetails(?\Externet\EpsBankTransfer\Generated\Protocol\V27\ShopResponseDetails $shopResponseDetails = null)
    {
        $this->shopResponseDetails = $shopResponseDetails;
        return $this;
    }

    /**
     * Gets as transactionDetailsRequest
     *
     * @return \Externet\EpsBankTransfer\Generated\Protocol\V27\TransactionDetailsRequest
     */
    public function getTransactionDetailsRequest()
    {
        return $this->transactionDetailsRequest;
    }

    /**
     * Sets a new transactionDetailsRequest
     *
     * @param \Externet\EpsBankTransfer\Generated\Protocol\V27\TransactionDetailsRequest $transactionDetailsRequest
     * @return self
     */
    public function setTransactionDetailsRequest(?\Externet\EpsBankTransfer\Generated\Protocol\V27\TransactionDetailsRequest $transactionDetailsRequest = null)
    {
        $this->transactionDetailsRequest = $transactionDetailsRequest;
        return $this;
    }

    /**
     * Gets as transactionDetailsResponse
     *
     * @return \Externet\EpsBankTransfer\Generated\Protocol\V27\TransactionDetailsResponse
     */
    public function getTransactionDetailsResponse()
    {
        return $this->transactionDetailsResponse;
    }

    /**
     * Sets a new transactionDetailsResponse
     *
     * @param \Externet\EpsBankTransfer\Generated\Protocol\V27\TransactionDetailsResponse $transactionDetailsResponse
     * @return self
     */
    public function setTransactionDetailsResponse(?\Externet\EpsBankTransfer\Generated\Protocol\V27\TransactionDetailsResponse $transactionDetailsResponse = null)
    {
        $this->transactionDetailsResponse = $transactionDetailsResponse;
        return $this;
    }

    /**
     * Gets as confirmationStatusRequest
     *
     * @return \Externet\EpsBankTransfer\Generated\Protocol\V27\ConfirmationStatusRequest
     */
    public function getConfirmationStatusRequest()
    {
        return $this->confirmationStatusRequest;
    }

    /**
     * Sets a new confirmationStatusRequest
     *
     * @param \Externet\EpsBankTransfer\Generated\Protocol\V27\ConfirmationStatusRequest $confirmationStatusRequest
     * @return self
     */
    public function setConfirmationStatusRequest(?\Externet\EpsBankTransfer\Generated\Protocol\V27\ConfirmationStatusRequest $confirmationStatusRequest = null)
    {
        $this->confirmationStatusRequest = $confirmationStatusRequest;
        return $this;
    }

    /**
     * Gets as confirmationStatusResponse
     *
     * @return \Externet\EpsBankTransfer\Generated\Protocol\V27\ConfirmationStatusResponse
     */
    public function getConfirmationStatusResponse()
    {
        return $this->confirmationStatusResponse;
    }

    /**
     * Sets a new confirmationStatusResponse
     *
     * @param \Externet\EpsBankTransfer\Generated\Protocol\V27\ConfirmationStatusResponse $confirmationStatusResponse
     * @return self
     */
    public function setConfirmationStatusResponse(?\Externet\EpsBankTransfer\Generated\Protocol\V27\ConfirmationStatusResponse $confirmationStatusResponse = null)
    {
        $this->confirmationStatusResponse = $confirmationStatusResponse;
        return $this;
    }

    /**
     * Gets as statusMsg
     *
     * @return \Externet\EpsBankTransfer\Generated\Protocol\V27\StatusMsg
     */
    public function getStatusMsg()
    {
        return $this->statusMsg;
    }

    /**
     * Sets a new statusMsg
     *
     * @param \Externet\EpsBankTransfer\Generated\Protocol\V27\StatusMsg $statusMsg
     * @return self
     */
    public function setStatusMsg(?\Externet\EpsBankTransfer\Generated\Protocol\V27\StatusMsg $statusMsg = null)
    {
        $this->statusMsg = $statusMsg;
        return $this;
    }


}


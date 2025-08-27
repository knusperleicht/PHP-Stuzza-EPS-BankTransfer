<?php

namespace Externet\EpsBankTransfer\Generated\Refund\EpsRefundRequest;

/**
 * Class representing EpsRefundRequestAType
 */
class EpsRefundRequestAType
{

    /**
     * @var \DateTime $creDtTm
     */
    private $creDtTm = null;

    /**
     * @var string $transactionId
     */
    private $transactionId = null;

    /**
     * @var string $merchantIBAN
     */
    private $merchantIBAN = null;

    /**
     * @var \Externet\EpsBankTransfer\Generated\Refund\Amount $amount
     */
    private $amount = null;

    /**
     * @var string $refundReference
     */
    private $refundReference = null;

    /**
     * @var \Externet\EpsBankTransfer\Generated\Refund\AuthenticationDetails $authenticationDetails
     */
    private $authenticationDetails = null;

    /**
     * Gets as creDtTm
     *
     * @return \DateTime
     */
    public function getCreDtTm()
    {
        return $this->creDtTm;
    }

    /**
     * Sets a new creDtTm
     *
     * @param \DateTime $creDtTm
     * @return self
     */
    public function setCreDtTm(\DateTime $creDtTm)
    {
        $this->creDtTm = $creDtTm;
        return $this;
    }

    /**
     * Gets as transactionId
     *
     * @return string
     */
    public function getTransactionId()
    {
        return $this->transactionId;
    }

    /**
     * Sets a new transactionId
     *
     * @param string $transactionId
     * @return self
     */
    public function setTransactionId($transactionId)
    {
        $this->transactionId = $transactionId;
        return $this;
    }

    /**
     * Gets as merchantIBAN
     *
     * @return string
     */
    public function getMerchantIBAN()
    {
        return $this->merchantIBAN;
    }

    /**
     * Sets a new merchantIBAN
     *
     * @param string $merchantIBAN
     * @return self
     */
    public function setMerchantIBAN($merchantIBAN)
    {
        $this->merchantIBAN = $merchantIBAN;
        return $this;
    }

    /**
     * Gets as amount
     *
     * @return \Externet\EpsBankTransfer\Generated\Refund\Amount
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * Sets a new amount
     *
     * @param \Externet\EpsBankTransfer\Generated\Refund\Amount $amount
     * @return self
     */
    public function setAmount(\Externet\EpsBankTransfer\Generated\Refund\Amount $amount)
    {
        $this->amount = $amount;
        return $this;
    }

    /**
     * Gets as refundReference
     *
     * @return string
     */
    public function getRefundReference()
    {
        return $this->refundReference;
    }

    /**
     * Sets a new refundReference
     *
     * @param string $refundReference
     * @return self
     */
    public function setRefundReference($refundReference)
    {
        $this->refundReference = $refundReference;
        return $this;
    }

    /**
     * Gets as authenticationDetails
     *
     * @return \Externet\EpsBankTransfer\Generated\Refund\AuthenticationDetails
     */
    public function getAuthenticationDetails()
    {
        return $this->authenticationDetails;
    }

    /**
     * Sets a new authenticationDetails
     *
     * @param \Externet\EpsBankTransfer\Generated\Refund\AuthenticationDetails $authenticationDetails
     * @return self
     */
    public function setAuthenticationDetails(\Externet\EpsBankTransfer\Generated\Refund\AuthenticationDetails $authenticationDetails)
    {
        $this->authenticationDetails = $authenticationDetails;
        return $this;
    }


}


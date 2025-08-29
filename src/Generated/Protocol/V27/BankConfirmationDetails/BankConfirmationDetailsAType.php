<?php

namespace Psa\EpsBankTransfer\Generated\Protocol\V27\BankConfirmationDetails;

/**
 * Class representing BankConfirmationDetailsAType
 */
class BankConfirmationDetailsAType
{

    /**
     * @var string $sessionId
     */
    private $sessionId = null;

    /**
     * @var \Psa\EpsBankTransfer\Generated\Payment\V27\PaymentConfirmationDetails $paymentConfirmationDetails
     */
    private $paymentConfirmationDetails = null;

    /**
     * Gets as sessionId
     *
     * @return string
     */
    public function getSessionId()
    {
        return $this->sessionId;
    }

    /**
     * Sets a new sessionId
     *
     * @param string $sessionId
     * @return self
     */
    public function setSessionId($sessionId)
    {
        $this->sessionId = $sessionId;
        return $this;
    }

    /**
     * Gets as paymentConfirmationDetails
     *
     * @return \Psa\EpsBankTransfer\Generated\Payment\V27\PaymentConfirmationDetails
     */
    public function getPaymentConfirmationDetails()
    {
        return $this->paymentConfirmationDetails;
    }

    /**
     * Sets a new paymentConfirmationDetails
     *
     * @param \Psa\EpsBankTransfer\Generated\Payment\V27\PaymentConfirmationDetails $paymentConfirmationDetails
     * @return self
     */
    public function setPaymentConfirmationDetails(\Psa\EpsBankTransfer\Generated\Payment\V27\PaymentConfirmationDetails $paymentConfirmationDetails)
    {
        $this->paymentConfirmationDetails = $paymentConfirmationDetails;
        return $this;
    }


}


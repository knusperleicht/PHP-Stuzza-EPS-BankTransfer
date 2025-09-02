<?php

namespace Knusperleicht\EpsBankTransfer\Internal\Generated\Protocol\V26\ConfirmationStatusResponse;

/**
 * Class representing ConfirmationStatusResponseAType
 */
class ConfirmationStatusResponseAType
{
    /**
     * @var string $sessionId
     */
    private $sessionId = null;

    /**
     * @var \Knusperleicht\EpsBankTransfer\Internal\Generated\Payment\V26\PaymentConfirmationDetails $paymentConfirmationDetails
     */
    private $paymentConfirmationDetails = null;

    /**
     * @var \Knusperleicht\EpsBankTransfer\Internal\Generated\Protocol\V26\ErrorDetails $errorDetails
     */
    private $errorDetails = null;

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
     * @return \Knusperleicht\EpsBankTransfer\Internal\Generated\Payment\V26\PaymentConfirmationDetails
     */
    public function getPaymentConfirmationDetails()
    {
        return $this->paymentConfirmationDetails;
    }

    /**
     * Sets a new paymentConfirmationDetails
     *
     * @param \Knusperleicht\EpsBankTransfer\Internal\Generated\Payment\V26\PaymentConfirmationDetails $paymentConfirmationDetails
     * @return self
     */
    public function setPaymentConfirmationDetails(?\Knusperleicht\EpsBankTransfer\Internal\Generated\Payment\V26\PaymentConfirmationDetails $paymentConfirmationDetails = null)
    {
        $this->paymentConfirmationDetails = $paymentConfirmationDetails;
        return $this;
    }

    /**
     * Gets as errorDetails
     *
     * @return \Knusperleicht\EpsBankTransfer\Internal\Generated\Protocol\V26\ErrorDetails
     */
    public function getErrorDetails()
    {
        return $this->errorDetails;
    }

    /**
     * Sets a new errorDetails
     *
     * @param \Knusperleicht\EpsBankTransfer\Internal\Generated\Protocol\V26\ErrorDetails $errorDetails
     * @return self
     */
    public function setErrorDetails(?\Knusperleicht\EpsBankTransfer\Internal\Generated\Protocol\V26\ErrorDetails $errorDetails = null)
    {
        $this->errorDetails = $errorDetails;
        return $this;
    }
}


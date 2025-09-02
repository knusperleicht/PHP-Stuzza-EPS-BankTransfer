<?php

namespace Knusperleicht\EpsBankTransfer\Internal\Generated\Protocol\V27\ConfirmationStatusRequest;

/**
 * Class representing ConfirmationStatusRequestAType
 */
class ConfirmationStatusRequestAType
{
    /**
     * @var string $transactionId
     */
    private $transactionId = null;

    /**
     * @var \Knusperleicht\EpsBankTransfer\Internal\Generated\Protocol\V27\AuthenticationDetails $authenticationDetails
     */
    private $authenticationDetails = null;

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
     * Gets as authenticationDetails
     *
     * @return \Knusperleicht\EpsBankTransfer\Internal\Generated\Protocol\V27\AuthenticationDetails
     */
    public function getAuthenticationDetails()
    {
        return $this->authenticationDetails;
    }

    /**
     * Sets a new authenticationDetails
     *
     * @param \Knusperleicht\EpsBankTransfer\Internal\Generated\Protocol\V27\AuthenticationDetails $authenticationDetails
     * @return self
     */
    public function setAuthenticationDetails(\Knusperleicht\EpsBankTransfer\Internal\Generated\Protocol\V27\AuthenticationDetails $authenticationDetails)
    {
        $this->authenticationDetails = $authenticationDetails;
        return $this;
    }
}


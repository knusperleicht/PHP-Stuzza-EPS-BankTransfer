<?php

namespace Knusperleicht\EpsBankTransfer\Internal\Generated\Protocol\V26\ShopResponseDetails;

/**
 * Class representing ShopResponseDetailsAType
 */
class ShopResponseDetailsAType
{
    /**
     * @var string $sessionId
     */
    private $sessionId = null;

    /**
     * @var \Knusperleicht\EpsBankTransfer\Internal\Generated\Payment\V26\ShopConfirmationDetails $shopConfirmationDetails
     */
    private $shopConfirmationDetails = null;

    /**
     * @var string $errorMsg
     */
    private $errorMsg = null;

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
     * Gets as shopConfirmationDetails
     *
     * @return \Knusperleicht\EpsBankTransfer\Internal\Generated\Payment\V26\ShopConfirmationDetails
     */
    public function getShopConfirmationDetails()
    {
        return $this->shopConfirmationDetails;
    }

    /**
     * Sets a new shopConfirmationDetails
     *
     * @param \Knusperleicht\EpsBankTransfer\Internal\Generated\Payment\V26\ShopConfirmationDetails $shopConfirmationDetails
     * @return self
     */
    public function setShopConfirmationDetails(?\Knusperleicht\EpsBankTransfer\Internal\Generated\Payment\V26\ShopConfirmationDetails $shopConfirmationDetails = null)
    {
        $this->shopConfirmationDetails = $shopConfirmationDetails;
        return $this;
    }

    /**
     * Gets as errorMsg
     *
     * @return string
     */
    public function getErrorMsg()
    {
        return $this->errorMsg;
    }

    /**
     * Sets a new errorMsg
     *
     * @param string $errorMsg
     * @return self
     */
    public function setErrorMsg($errorMsg)
    {
        $this->errorMsg = $errorMsg;
        return $this;
    }
}


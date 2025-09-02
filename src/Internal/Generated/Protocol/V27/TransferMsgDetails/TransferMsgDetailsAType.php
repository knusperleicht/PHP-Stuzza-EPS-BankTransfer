<?php

namespace Knusperleicht\EpsBankTransfer\Internal\Generated\Protocol\V27\TransferMsgDetails;

/**
 * Class representing TransferMsgDetailsAType
 */
class TransferMsgDetailsAType
{
    /**
     * @var string $confirmationUrl
     */
    private $confirmationUrl = null;

    /**
     * @var \Knusperleicht\EpsBankTransfer\Internal\Generated\Protocol\V27\TransactionOkUrl $transactionOkUrl
     */
    private $transactionOkUrl = null;

    /**
     * @var \Knusperleicht\EpsBankTransfer\Internal\Generated\Protocol\V27\TransactionNokUrl $transactionNokUrl
     */
    private $transactionNokUrl = null;

    /**
     * Gets as confirmationUrl
     *
     * @return string
     */
    public function getConfirmationUrl()
    {
        return $this->confirmationUrl;
    }

    /**
     * Sets a new confirmationUrl
     *
     * @param string $confirmationUrl
     * @return self
     */
    public function setConfirmationUrl($confirmationUrl)
    {
        $this->confirmationUrl = $confirmationUrl;
        return $this;
    }

    /**
     * Gets as transactionOkUrl
     *
     * @return \Knusperleicht\EpsBankTransfer\Internal\Generated\Protocol\V27\TransactionOkUrl
     */
    public function getTransactionOkUrl()
    {
        return $this->transactionOkUrl;
    }

    /**
     * Sets a new transactionOkUrl
     *
     * @param \Knusperleicht\EpsBankTransfer\Internal\Generated\Protocol\V27\TransactionOkUrl $transactionOkUrl
     * @return self
     */
    public function setTransactionOkUrl(\Knusperleicht\EpsBankTransfer\Internal\Generated\Protocol\V27\TransactionOkUrl $transactionOkUrl)
    {
        $this->transactionOkUrl = $transactionOkUrl;
        return $this;
    }

    /**
     * Gets as transactionNokUrl
     *
     * @return \Knusperleicht\EpsBankTransfer\Internal\Generated\Protocol\V27\TransactionNokUrl
     */
    public function getTransactionNokUrl()
    {
        return $this->transactionNokUrl;
    }

    /**
     * Sets a new transactionNokUrl
     *
     * @param \Knusperleicht\EpsBankTransfer\Internal\Generated\Protocol\V27\TransactionNokUrl $transactionNokUrl
     * @return self
     */
    public function setTransactionNokUrl(\Knusperleicht\EpsBankTransfer\Internal\Generated\Protocol\V27\TransactionNokUrl $transactionNokUrl)
    {
        $this->transactionNokUrl = $transactionNokUrl;
        return $this;
    }
}


<?php

namespace Externet\EpsBankTransfer\Generated\Protocol\V27\TransferMsgDetails;

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
     * @var \Externet\EpsBankTransfer\Generated\Protocol\V27\TransactionOkUrl $transactionOkUrl
     */
    private $transactionOkUrl = null;

    /**
     * @var \Externet\EpsBankTransfer\Generated\Protocol\V27\TransactionNokUrl $transactionNokUrl
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
     * @return \Externet\EpsBankTransfer\Generated\Protocol\V27\TransactionOkUrl
     */
    public function getTransactionOkUrl()
    {
        return $this->transactionOkUrl;
    }

    /**
     * Sets a new transactionOkUrl
     *
     * @param \Externet\EpsBankTransfer\Generated\Protocol\V27\TransactionOkUrl $transactionOkUrl
     * @return self
     */
    public function setTransactionOkUrl(\Externet\EpsBankTransfer\Generated\Protocol\V27\TransactionOkUrl $transactionOkUrl)
    {
        $this->transactionOkUrl = $transactionOkUrl;
        return $this;
    }

    /**
     * Gets as transactionNokUrl
     *
     * @return \Externet\EpsBankTransfer\Generated\Protocol\V27\TransactionNokUrl
     */
    public function getTransactionNokUrl()
    {
        return $this->transactionNokUrl;
    }

    /**
     * Sets a new transactionNokUrl
     *
     * @param \Externet\EpsBankTransfer\Generated\Protocol\V27\TransactionNokUrl $transactionNokUrl
     * @return self
     */
    public function setTransactionNokUrl(\Externet\EpsBankTransfer\Generated\Protocol\V27\TransactionNokUrl $transactionNokUrl)
    {
        $this->transactionNokUrl = $transactionNokUrl;
        return $this;
    }


}


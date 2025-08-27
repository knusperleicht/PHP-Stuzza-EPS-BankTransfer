<?php
declare(strict_types=1);

namespace Externet\EpsBankTransfer;

class TransferMsgDetails
{

    /** @var string The URL where the eps SO sends the vitality check and eps payment confirmation message */
    private $confirmationUrl;

    /** @var string The URL to guarantee a continuous flow for the buyer and offer a return point to the merchant's webshop */
    private $transactionOkUrl;

    /** @var string If the transaction was not completed successfully, the buyer will be redirected to this URL after system feedback */
    private $transactionNokUrl;

    /** @var string The window into which the redirect to the ok URL should occur */
    private $targetWindowOk;

    /** @var string The window into which the redirect to the nok URL should occur */
    private $targetWindowNok;

    /**
     *
     * @param string $confirmationUrl
     * @param string $transactionOkUrl
     * @param string $transactionNokUrl
     */
    public function __construct(string $confirmationUrl, string $transactionOkUrl, string $transactionNokUrl)
    {
        $this->confirmationUrl = $confirmationUrl;
        $this->transactionOkUrl = $transactionOkUrl;
        $this->transactionNokUrl = $transactionNokUrl;
    }

    public function getConfirmationUrl(): string
    {
        return $this->confirmationUrl;
    }

    public function setConfirmationUrl(string $confirmationUrl): void
    {
        $this->confirmationUrl = $confirmationUrl;
    }

    public function getTransactionOkUrl(): string
    {
        return $this->transactionOkUrl;
    }

    public function setTransactionOkUrl(string $transactionOkUrl): void
    {
        $this->transactionOkUrl = $transactionOkUrl;
    }

    public function getTransactionNokUrl(): string
    {
        return $this->transactionNokUrl;
    }

    public function setTransactionNokUrl(string $transactionNokUrl): void
    {
        $this->transactionNokUrl = $transactionNokUrl;
    }

    public function getTargetWindowOk(): string
    {
        return $this->targetWindowOk;
    }

    public function setTargetWindowOk(string $targetWindowOk): void
    {
        $this->targetWindowOk = $targetWindowOk;
    }

    public function getTargetWindowNok(): string
    {
        return $this->targetWindowNok;
    }

    public function setTargetWindowNok(string $targetWindowNok): void
    {
        $this->targetWindowNok = $targetWindowNok;
    }
}
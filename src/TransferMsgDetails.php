<?php
declare(strict_types=1);

namespace Externet\EpsBankTransfer;

class TransferMsgDetails
{

    /** @var string The URL where the eps SO sends the vitality check and eps payment confirmation message */
    public $confirmationUrl;

    /** @var string The URL to guarantee a continuous flow for the buyer and offer a return point to the merchant's webshop */
    public $transactionOkUrl;

    /** @var string If the transaction was not completed successfully, the buyer will be redirected to this URL after system feedback */
    public $transactionNokUrl;

    /** @var string The window into which the redirect to the ok URL should occur */
    public $targetWindowOk;

    /** @var string The window into which the redirect to the nok URL should occur */
    public $targetWindowNok;

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

}
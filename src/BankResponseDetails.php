<?php
declare(strict_types=1);

namespace Externet\EpsBankTransfer;
class BankResponseDetails
{
    /** @var string The URL that the buyer will be redirected to */
    public $clientRedirectUrl;

    /** @var string Error code, '000' means no error */
    public $errorCode;

    /** @var string Error message if any */
    public $errorMsg;

    /** @var string Transaction identifier */
    public $transactionId;

    /** @var string URL for QR code */
    public $qrCodeUrl;

    public function __construct(string $clientRedirectUrl, string $errorCode, string $errorMsg, string $transactionId, string $qrCodeUrl)
    {
        $this->clientRedirectUrl = $clientRedirectUrl;
        $this->errorCode = $errorCode;
        $this->errorMsg = $errorMsg;
        $this->transactionId = $transactionId;
        $this->qrCodeUrl = $qrCodeUrl;
    }
}
<?php
declare(strict_types=1);

namespace Externet\EpsBankTransfer;
use Externet\EpsBankTransfer\Generated\Refund\EpsRefundRequest;
use Externet\EpsBankTransfer\Generated\Refund\Amount;
use Externet\EpsBankTransfer\Generated\Refund\AuthenticationDetails;
use Externet\EpsBankTransfer\Utilities\Fingerprint;

class EpsRefundRequestWrapped
{
    /**
     * @var string Creation timestamp in ISO 8601 format
     */
    public $creDtTm;

    /**
     * @var string Transaction identifier (1-36 chars: [a-zA-Z0-9-._~])
     */
    public $transactionId;

    /**
     * @var string Merchant IBAN (up to 34 chars)
     */
    public $merchantIban;

    /**
     * @var float|string Refund amount
     */
    public $amount;

    /**
     * @var string Three-letter currency code
     */
    public $amountCurrencyIdentifier;

    /**
     * @var string|null Reference ID for refund (max 35 chars)
     */
    public $refundReference;

    /**
     * @var string User ID (max 25 chars)
     */
    public $userId;

    /**
     * @var string Authentication PIN
     */
    public $pin;

    public function __construct(
        string  $creDtTm,
        string  $transactionId,
        string  $merchantIban,
                $amount,
        string  $amountCurrencyIdentifier,
        string  $userId,
        string  $pin,
        ?string $refundReference = null
    )
    {
        $this->creDtTm = $creDtTm;
        $this->transactionId = $transactionId;
        $this->merchantIban = $merchantIban;
        $this->amount = $amount;
        $this->amountCurrencyIdentifier = $amountCurrencyIdentifier;
        $this->refundReference = $refundReference;
        $this->userId = $userId;
        $this->pin = $pin;
    }

    /**
     * @throws \Exception
     */
    public function buildRefundRequest(): EpsRefundRequest
    {
        $refundRequest = new EpsRefundRequest();

        $refundRequest->setCreDtTm(new \DateTime($this->creDtTm));
        $refundRequest->setTransactionId($this->transactionId);
        $refundRequest->setMerchantIBAN($this->merchantIban);

        $amount = new Amount($this->amount);
        $amount->setAmountCurrencyIdentifier($this->amountCurrencyIdentifier);
        $refundRequest->setAmount($amount);

        if (!empty($this->refundReference)) {
            $refundRequest->setRefundReference($this->refundReference);
        }

        $auth = new AuthenticationDetails();
        $auth->setUserId($this->userId);

        $fingerprint = Fingerprint::generateSHA256Fingerprint(
            $this->pin,
            $this->creDtTm,
            $this->transactionId,
            $this->merchantIban,
            $this->amount,
            $this->amountCurrencyIdentifier,
            $this->userId,
            $this->refundReference
        );
        $auth->setSHA256Fingerprint($fingerprint);

        $refundRequest->setAuthenticationDetails($auth);

        return $refundRequest;
    }

}
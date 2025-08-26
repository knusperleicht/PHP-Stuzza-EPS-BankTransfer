<?php
declare(strict_types=1);

namespace Externet\EpsBankTransfer;

class EpsRefundRequest
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
    public function getSimpleXml(): EpsXmlElement
    {
        // Create an XML document with a root element and namespaces
        $xml = EpsXmlElement::createEmptySimpleXml(
            'epsr:EpsRefundRequest xmlns:epsr="http://www.stuzza.at/namespaces/eps/refund/2018/09" xmlns:dsig="http://www.w3.org/2000/09/xmldsig#"'
        );

        // Add mandatory elements
        $xml->addChildExt('CreDtTm', $this->creDtTm, 'epsr');
        $xml->addChildExt('TransactionId', $this->transactionId, 'epsr');
        $xml->addChildExt('MerchantIBAN', $this->merchantIban, 'epsr');

        // Add amount with currency
        $amountElement = $xml->addChildExt('Amount', (string)$this->amount, 'epsr');
        $amountElement->addAttribute('AmountCurrencyIdentifier', $this->amountCurrencyIdentifier);

        // Add optional reference
        if (!empty($this->refundReference)) {
            $xml->addChildExt('RefundReference', $this->refundReference, 'epsr');
        }

        // Add authentication details
        $authElement = $xml->addChildExt('AuthenticationDetails', '', 'epsr');
        $authElement->addChildExt('UserId', $this->userId, 'epsr');

        // Add fingerprint
        $authElement->addChildExt(
            'SHA256Fingerprint',
            $this->generateSHA256Fingerprint(
                $this->pin,
                $this->creDtTm,
                $this->transactionId,
                $this->merchantIban,
                $this->amount,
                $this->amountCurrencyIdentifier,
                $this->userId,
                $this->refundReference
            ),
            'epsr'
        );

        return $xml;
    }

    private function generateSHA256Fingerprint($pin, $creationDateTime, $transactionId, $merchantIban, $amountValue, $amountCurrency, $userId, $refundReference = null): string
    {
        $inputData = $pin .
            $creationDateTime .
            $transactionId .
            $merchantIban .
            $amountValue .
            $amountCurrency .
            $refundReference .
            $userId;

        return strtoupper(hash('sha256', $inputData));
    }
}
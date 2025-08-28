<?php
declare(strict_types=1);

namespace Externet\EpsBankTransfer\Responses;

use Externet\EpsBankTransfer\Generated\Payment\V26\ShopConfirmationDetails;
use Externet\EpsBankTransfer\Generated\Protocol\V26\EpsProtocolDetails;

class ShopResponseDetails
{

    /** @var string Hinweis vom Händler über den aufgetretenen Fehler */
    public $ErrorMsg;

    /** @var string die von der Bank generierte Session Kennung */
    public $SessionId;

    /** @var string der von der Bank übermittelte Status zur eps Transaktion */
    public $StatusCode;

    /** @var string die von der Bank generierte Ersterfasserreferenz */
    public $PaymentReferenceIdentifier;

    public function buildShopResponseDetails(): EpsProtocolDetails
    {
        $epsProtocolDetails = new EpsProtocolDetails();
        $shopResponseDetails = new \Externet\EpsBankTransfer\Generated\Protocol\V26\ShopResponseDetails();

        if (!empty($this->ErrorMsg)) {
            $shopResponseDetails->setErrorMsg($this->ErrorMsg);
            if (!empty($this->SessionId)) {
                $shopResponseDetails->setSessionId($this->SessionId);
            }
        } else {
            $shopResponseDetails->setSessionId($this->SessionId);

            $shopConfirmationDetails = new ShopConfirmationDetails();
            $shopConfirmationDetails->setStatusCode($this->StatusCode);
            $shopConfirmationDetails->setPaymentReferenceIdentifier($this->PaymentReferenceIdentifier);
            $shopResponseDetails->setShopConfirmationDetails($shopConfirmationDetails);
        }

        $epsProtocolDetails->setShopResponseDetails($shopResponseDetails);
        return $epsProtocolDetails;
    }

    /**
     * @param string $StatusCode
     */
    public function setStatusCode(string $StatusCode): void
    {
        $this->StatusCode = $StatusCode;
    }

    /**
     * @param string $SessionId
     */
    public function setSessionId(string $SessionId): void
    {
        $this->SessionId = $SessionId;
    }

    /**
     * @param string $PaymentReferenceIdentifier
     */
    public function setPaymentReferenceIdentifier(string $PaymentReferenceIdentifier): void
    {
        $this->PaymentReferenceIdentifier = $PaymentReferenceIdentifier;
    }

    /**
     * @param string $ErrorMsg
     */
    public function setErrorMsg(string $ErrorMsg): void
    {
        $this->ErrorMsg = $ErrorMsg;
    }
}
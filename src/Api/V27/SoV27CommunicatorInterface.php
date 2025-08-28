<?php
declare(strict_types=1);

namespace Externet\EpsBankTransfer\Api\V27;

use Externet\EpsBankTransfer\Api\SoCommunicatorInterface;
use Externet\EpsBankTransfer\Generated\Protocol\V27\EpsProtocolDetails;
use Externet\EpsBankTransfer\Generated\Refund\EpsRefundResponse;
use Externet\EpsBankTransfer\Requests\InitiateTransferRequest;
use Externet\EpsBankTransfer\Requests\RefundRequest;

interface SoV27CommunicatorInterface extends SoCommunicatorInterface
{
    /**
     * Sends a payment initiation request to EPS (Protocol v2.7).
     *
     * @param InitiateTransferRequest $transferInitiatorDetails
     * @param string|null $targetUrl
     * @return EpsProtocolDetails
     */
    public function initiateTransferRequest(
        InitiateTransferRequest $transferInitiatorDetails,
        ?string                 $targetUrl = null
    ): EpsProtocolDetails;
}
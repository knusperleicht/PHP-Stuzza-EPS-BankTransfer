<?php
declare(strict_types=1);

namespace Externet\EpsBankTransfer\Api\V26;

use Externet\EpsBankTransfer\Api\SoCommunicatorInterface;
use Externet\EpsBankTransfer\Generated\Protocol\V26\EpsProtocolDetails;
use Externet\EpsBankTransfer\Generated\Refund\EpsRefundResponse;
use Externet\EpsBankTransfer\Requests\InitiateTransferRequest;
use Externet\EpsBankTransfer\Requests\RefundRequest;

interface SoV26CommunicatorInterface extends SoCommunicatorInterface
{
    /**
     * Sends a payment initiation request to EPS (Protocol v2.6).
     *
     * @param InitiateTransferRequest $transferInitiatorDetails
     * @param string|null $targetUrl
     * @return EpsProtocolDetails
     */
    public function initiateTransferRequest(
        InitiateTransferRequest $transferInitiatorDetails,
        ?string                 $targetUrl = null
    ): EpsProtocolDetails;

    /**
     * Sends a refund request to EPS (Protocol v2.6).
     *
     * @param RefundRequest $refundRequest
     * @param string|null $targetUrl
     * @return EpsRefundResponse
     */
    public function sendRefundRequest(
        RefundRequest $refundRequest,
        ?string       $targetUrl = null
    ): EpsRefundResponse;
}
<?php
declare(strict_types=1);

namespace Externet\EpsBankTransfer\Api;

use Externet\EpsBankTransfer\Generated\BankList\EpsSOBankListProtocol;
use Externet\EpsBankTransfer\Requests\InitiateTransferRequest;
use Externet\EpsBankTransfer\Requests\RefundRequest;

interface SoCommunicatorInterface
{
    /**
     * @return EpsSOBankListProtocol
     */
    public function getBanks(): EpsSOBankListProtocol;

    /**
     * @param InitiateTransferRequest $transferInitiatorDetails
     * @return object Protocol details object (version-specific)
     */
    public function sendTransferInitiatorDetails(
        InitiateTransferRequest $transferInitiatorDetails
    );

    /**
     * @param callable|null $confirmationCallback
     * @param callable|null $vitalityCheckCallback
     * @param string $rawPostStream
     * @param string $outputStream
     * @return void
     */
    public function handleConfirmationUrl(
        $confirmationCallback = null,
        $vitalityCheckCallback = null,
        string $rawPostStream = 'php://input',
        string $outputStream = 'php://output'
    );

    /**
     * @param RefundRequest $refundRequest
     * @param string|null $logMessage
     * @return object Refund response (version-specific)
     */
    public function sendRefundRequest(
        RefundRequest $refundRequest,
        ?string $logMessage = null
    );

    public function setObscuritySuffixLength(int $obscuritySuffixLength);

    public function setObscuritySeed(?string $obscuritySeed);

    public function setBaseUrl(string $baseUrl);
}

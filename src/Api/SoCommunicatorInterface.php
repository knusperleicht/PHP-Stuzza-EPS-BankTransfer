<?php
declare(strict_types=1);

namespace Externet\EpsBankTransfer\Api;

use Externet\EpsBankTransfer\Requests\InitiateTransferRequest;
use Externet\EpsBankTransfer\Requests\RefundRequest;

interface SoCommunicatorInterface
{
    /**
     * @return array<string,array<string,string>>
     */
    public function getBanksArray();

    /**
     * @return array<string,array<string,string>>|null
     */
    public function tryGetBanksArray();

    /**
     * @param bool $validateXml
     * @return string
     */
    public function getBanks(bool $validateXml = true);

    /**
     * @param InitiateTransferRequest $transferInitiatorDetails
     * @param string|null $targetUrl
     * @return object Protocol details object (version-specific)
     */
    public function sendTransferInitiatorDetails(
        InitiateTransferRequest $transferInitiatorDetails,
        ?string $targetUrl = null
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
     * @param string|null $targetUrl
     * @param string|null $logMessage
     * @return object Refund response (version-specific)
     */
    public function sendRefundRequest(
        RefundRequest $refundRequest,
        ?string $targetUrl = null,
        ?string $logMessage = null
    );

    public function setObscuritySuffixLength(int $obscuritySuffixLength);

    public function setObscuritySeed(?string $obscuritySeed);

    public function setBaseUrl(string $baseUrl);
}

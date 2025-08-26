<?php

namespace Externet\EpsBankTransfer\Api;

use Externet\EpsBankTransfer\EpsProtocolDetails;
use Externet\EpsBankTransfer\EpsRefundRequest;
use Externet\EpsBankTransfer\EpsRefundResponse;
use Externet\EpsBankTransfer\TransferInitiatorDetails;

interface SoCommunicatorInterface
{
    /**
     * Retrieves available banks from the EPS system.
     *
     * @return array<string,array<string,string>> Bank list indexed by bank name.
     */
    public function getBanksArray(): array;

    /**
     * Attempts to retrieve available banks from the EPS system.
     *
     * @return array<string,array<string,string>>|null Bank list indexed by bank name or null if retrieval fails.
     */
    public function tryGetBanksArray(): ?array;

    /**
     * Fetches the XML list of banks from EPS.
     *
     * @param bool $validateXml Whether to validate the response XML.
     *
     * @return string Raw XML response.
     */
    public function getBanks(bool $validateXml = true): string;

    /**
     * Sends a payment initiation request to EPS.
     *
     * @param TransferInitiatorDetails $transferInitiatorDetails Payment request details.
     * @param string|null $targetUrl Optional target URL.
     *
     * @return EpsProtocolDetails The protocol response details from EPS.
     */
    public function sendTransferInitiatorDetails(
        TransferInitiatorDetails $transferInitiatorDetails,
        ?string                  $targetUrl = null
    ): EpsProtocolDetails;

    /**
     * Handles confirmation callbacks from EPS.
     *
     * @param callable|null $confirmationCallback Callback for payment confirmation.
     * @param callable|null $vitalityCheckCallback Optional callback for vitality checks.
     * @param string $rawPostStream Input stream (default: php://input).
     * @param string $outputStream Output stream (default: php://output).
     */
    public function handleConfirmationUrl(
        $confirmationCallback = null,
        $vitalityCheckCallback = null,
        $rawPostStream = 'php://input',
        $outputStream = 'php://output'
    ): void;

    /**
     * Sends a refund request to EPS.
     *
     * @param EpsRefundRequest $refundRequest Refund request object.
     * @param string|null $targetUrl Optional target URL.
     * @param string|null $logMessage Optional log message.
     *
     * @return EpsRefundResponse Response containing status code and optional error message.
     */
    public function sendRefundRequest(
        EpsRefundRequest $refundRequest,
        ?string          $targetUrl = null,
        ?string          $logMessage = null
    ): EpsRefundResponse;

    /**
     * Registers a callback for logging.
     *
     * @param callable $callback Signature: function(string $message): void
     */
    public function setLogCallback(callable $callback): void;

    /**
     * Configure suffix length for remittance hash.
     */
    public function setObscuritySuffixLength(int $obscuritySuffixLength): void;

    /**
     * Configure seed for remittance hash.
     */
    public function setObscuritySeed(?string $obscuritySeed): void;

    /**
     * Override the base URL (live vs. test).
     */
    public function setBaseUrl(string $baseUrl): void;
}

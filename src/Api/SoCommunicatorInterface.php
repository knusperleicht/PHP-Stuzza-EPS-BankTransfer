<?php
declare(strict_types=1);

namespace Externet\EpsBankTransfer\Api;

use Externet\EpsBankTransfer\Requests\InitiateTransferRequest;

interface SoCommunicatorInterface
{
    /**
     * @param InitiateTransferRequest $transferInitiatorDetails
     * @param string|null $targetUrl
     * @return object Protocol details object (version-specific)
     */
    public function initiateTransferRequest(
        InitiateTransferRequest $transferInitiatorDetails,
        ?string                 $targetUrl = null
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
}

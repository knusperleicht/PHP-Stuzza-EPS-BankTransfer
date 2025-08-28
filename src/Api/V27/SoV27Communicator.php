<?php
declare(strict_types=1);

namespace Externet\EpsBankTransfer\Api\V27;

use Externet\EpsBankTransfer\Api\AbstractSoCommunicator;
use Externet\EpsBankTransfer\Generated\Protocol\V27\EpsProtocolDetails;
use Externet\EpsBankTransfer\Requests\InitiateTransferRequest;

class SoV27Communicator extends AbstractSoCommunicator implements SoV27CommunicatorInterface
{
    public function initiateTransferRequest(
        InitiateTransferRequest $transferInitiatorDetails,
        ?string $targetUrl = null
    ): EpsProtocolDetails {
        throw new \LogicException('Not implemented yet - waiting for XSD 2.7');

    }

    public function handleConfirmationUrl(
        $confirmationCallback = null,
        $vitalityCheckCallback = null,
        string $rawPostStream = 'php://input',
        string $outputStream = 'php://output'
    ): void {
        throw new \LogicException('Not implemented yet - waiting for XSD 2.7');
    }
}

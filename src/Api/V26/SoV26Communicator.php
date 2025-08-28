<?php
declare(strict_types=1);

namespace Externet\EpsBankTransfer\Api\V26;

use Exception;
use Externet\EpsBankTransfer\Api\AbstractSoCommunicator;
use Externet\EpsBankTransfer\Exceptions\CallbackResponseException;
use Externet\EpsBankTransfer\Exceptions\InvalidCallbackException;
use Externet\EpsBankTransfer\Exceptions\ShopResponseException;
use Externet\EpsBankTransfer\Exceptions\XmlValidationException;
use Externet\EpsBankTransfer\Generated\Protocol\V26\EpsProtocolDetails;
use Externet\EpsBankTransfer\Requests\InitiateTransferRequest;
use Externet\EpsBankTransfer\Responses\ShopResponseDetails;
use Externet\EpsBankTransfer\Utilities\XmlValidator;

class SoV26Communicator extends AbstractSoCommunicator implements SoV26CommunicatorInterface
{
    /**
     * @throws XmlValidationException
     */
    public function initiateTransferRequest(
        InitiateTransferRequest $transferInitiatorDetails,
        ?string $targetUrl = null
    ): EpsProtocolDetails {
        if ($transferInitiatorDetails->remittanceIdentifier !== null) {
            $transferInitiatorDetails->remittanceIdentifier =
                $this->core->appendHash($transferInitiatorDetails->remittanceIdentifier);
        }

        if ($transferInitiatorDetails->unstructuredRemittanceIdentifier !== null) {
            $transferInitiatorDetails->unstructuredRemittanceIdentifier =
                $this->core->appendHash($transferInitiatorDetails->unstructuredRemittanceIdentifier);
        }

        $targetUrl = $targetUrl ?? $this->core->getBaseUrl() . '/transinit/eps/v2_6';

        $xmlData = $this->serializer->serialize($transferInitiatorDetails, 'xml');
        $response = $this->core->postUrl($targetUrl, $xmlData, 'Send payment order');

        XmlValidator::ValidateEpsProtocol($response);

        /** @var EpsProtocolDetails $protocolDetails */
        return $this->serializer->deserialize($response, EpsProtocolDetails::class, 'xml');
    }

    /**
     * @throws InvalidCallbackException
     * @throws XmlValidationException
     * @throws ShopResponseException
     * @throws CallbackResponseException
     */
    public function handleConfirmationUrl(
        $confirmationCallback = null,
        $vitalityCheckCallback = null,
        string $rawPostStream = 'php://input',
        string $outputStream = 'php://output'
    ): void
    {
        try {
            if ($confirmationCallback === null || !is_callable($confirmationCallback)) {
                throw new InvalidCallbackException('confirmationCallback not callable or missing');
            }
            if ($vitalityCheckCallback !== null && !is_callable($vitalityCheckCallback)) {
                throw new InvalidCallbackException('vitalityCheckCallback not callable');
            }

            $rawXml = file_get_contents($rawPostStream);
            XmlValidator::ValidateEpsProtocol($rawXml);

            /** @var EpsProtocolDetails $protocol */
            $protocol = $this->serializer->deserialize($rawXml, EpsProtocolDetails::class, 'xml');
            $shopConfirmationDetails = new ShopResponseDetails();

            if ($protocol->getVitalityCheckDetails() !== null) {
                $this->handleVitalityCheck($vitalityCheckCallback, $rawXml, $protocol->getVitalityCheckDetails(), $outputStream);
                return;
            }

            if ($protocol->getBankConfirmationDetails() !== null) {
                $this->handleBankConfirmation($confirmationCallback, $rawXml, $protocol->getBankConfirmationDetails(), $shopConfirmationDetails, $outputStream);
                return;
            }

            throw new XmlValidationException('Unknown confirmation details structure');

        } catch (Exception $e) {
            $this->handleException($e, $outputStream);
            throw $e;
        }
    }

    /**
     * @throws CallbackResponseException
     */
    private function handleVitalityCheck(?callable $callback, string $rawXml, $vitality, string $outputStream): void
    {
        if ($callback !== null) {
            if (call_user_func($callback, $rawXml, $vitality) !== true) {
                throw new CallbackResponseException('Vitality check callback must return true');
            }
        }
        file_put_contents($outputStream, $rawXml);
    }

    /**
     * @throws CallbackResponseException
     */
    private function handleBankConfirmation(callable $callback, string $rawXml, $confirmation, ShopResponseDetails $response, string $outputStream): void
    {
        $response->setSessionId($confirmation->getSessionId());
        $response->setStatusCode($confirmation->getPaymentConfirmationDetails()->getStatusCode());
        $response->setPaymentReferenceIdentifier(
            $confirmation->getPaymentConfirmationDetails()->getPaymentReferenceIdentifier()
        );

        if (call_user_func($callback, $rawXml, $confirmation) !== true) {
            throw new CallbackResponseException('Confirmation callback must return true');
        }

        $xml = $this->serializer->serialize($response->buildShopResponseDetails(), 'xml');
        file_put_contents($outputStream, $xml);
    }

    private function handleException(Exception $e, string $outputStream): void
    {
        $shopConfirmationDetails = new ShopResponseDetails();

        if ($e instanceof ShopResponseException) {
            $shopConfirmationDetails->setErrorMsg($e->getShopResponseErrorMessage());
        } else {
            $shopConfirmationDetails->setErrorMsg('Exception "' . get_class($e) . '" occurred during confirmation handling');
        }

        file_put_contents($outputStream, $this->serializer->serialize($shopConfirmationDetails->buildShopResponseDetails(), 'xml'));
    }
}

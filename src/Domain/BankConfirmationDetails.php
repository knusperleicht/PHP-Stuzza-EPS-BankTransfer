<?php
declare(strict_types=1);

namespace Knusperleicht\EpsBankTransfer\Domain;

use DateTimeInterface;
use Exception;
use Knusperleicht\EpsBankTransfer\Internal\Generated\Protocol\V26\EpsProtocolDetails;

/**
 * Domain representation of an EPS Bank Confirmation (v2.6).
 */
class BankConfirmationDetails
{
    /** @var string */
    private $sessionId;
    /** @var string */
    private $remittanceIdentifier;
    /** @var string */
    private $approvingUnitBankIdentifier;
    /** @var DateTimeInterface */
    private $approvalTime;
    /** @var string */
    private $paymentReferenceIdentifier;
    /** @var string */
    private $statusCode;
    /** @var string|null */
    private $referenceIdentifier;
    /** @var string|null */
    private $orderingCustomerNameAddressText;
    /** @var string|null */
    private $orderingCustomerIdentifier;
    /** @var string|null */
    private $orderingCustomerBic;

    public function __construct(
        string            $sessionId,
        string            $remittanceIdentifier,
        string            $approvingUnitBankIdentifier,
        DateTimeInterface $approvalTime,
        string            $paymentReferenceIdentifier,
        string            $statusCode,
        ?string           $referenceIdentifier = null,
        ?string           $orderingCustomerNameAddressText = null,
        ?string           $orderingCustomerIdentifier = null,
        ?string           $orderingCustomerBic = null
    )
    {
        $this->sessionId = $sessionId;
        $this->remittanceIdentifier = $remittanceIdentifier;
        $this->approvingUnitBankIdentifier = $approvingUnitBankIdentifier;
        $this->approvalTime = $approvalTime;
        $this->paymentReferenceIdentifier = $paymentReferenceIdentifier;
        $this->statusCode = $statusCode;
        $this->referenceIdentifier = $referenceIdentifier;
        $this->orderingCustomerNameAddressText = $orderingCustomerNameAddressText;
        $this->orderingCustomerIdentifier = $orderingCustomerIdentifier;
        $this->orderingCustomerBic = $orderingCustomerBic;
    }

    public function getSessionId(): string
    {
        return $this->sessionId;
    }

    public function getRemittanceIdentifier(): string
    {
        return $this->remittanceIdentifier;
    }

    public function getApprovingUnitBankIdentifier(): string
    {
        return $this->approvingUnitBankIdentifier;
    }

    public function getApprovalTime(): DateTimeInterface
    {
        return $this->approvalTime;
    }

    public function getPaymentReferenceIdentifier(): string
    {
        return $this->paymentReferenceIdentifier;
    }

    public function getStatusCode(): string
    {
        return $this->statusCode;
    }

    public function getReferenceIdentifier(): ?string
    {
        return $this->referenceIdentifier;
    }

    public function getOrderingCustomerNameAddressText(): ?string
    {
        return $this->orderingCustomerNameAddressText;
    }

    public function getOrderingCustomerIdentifier(): ?string
    {
        return $this->orderingCustomerIdentifier;
    }

    public function getOrderingCustomerBic(): ?string
    {
        return $this->orderingCustomerBic;
    }

    /**
     * Factory method: create Domain object from generated XSD object.
     *
     * @param EpsProtocolDetails $epsProtocolDetails Parsed protocol details containing confirmation payload
     * @return self
     * @throws Exception When required fields are missing or invalid in the payload
     */
    public static function fromV26(EpsProtocolDetails $epsProtocolDetails): self
    {
        $bankConfirmation = $epsProtocolDetails->getBankConfirmationDetails();
        $pcd = $bankConfirmation->getPaymentConfirmationDetails();

        // According to XSD: one of the choice elements is always present at PaymentConfirmationDetails
        $remittanceId = $pcd->getRemittanceIdentifier()
            ?? $pcd->getUnstructuredRemittanceIdentifier();

        $referenceIdentifier = null;
        $orderingCustomerNameAddressText = null;
        $orderingCustomerIdentifier = null;
        $orderingCustomerBic = null;

        if ($remittanceId === null && $pcd->getPaymentInitiatorDetails() !== null) {
            $pid = $pcd->getPaymentInitiatorDetails();
            $epi = $pid->getEpiDetails();
            $paymentInstructionDetails = $epi->getPaymentInstructionDetails();

            $remittanceId = $paymentInstructionDetails->getRemittanceIdentifier()
                ?? $paymentInstructionDetails->getUnstructuredRemittanceIdentifier();

            $identificationDetails = $epi->getIdentificationDetails();

            $referenceIdentifier = $identificationDetails->getReferenceIdentifier() ?? null;
            $orderingCustomerNameAddressText = $identificationDetails->getOrderingCustomerNameAddressText() ?? null;
            $orderingCustomerIdentifier = $identificationDetails->getOrderingCustomerIdentifier() ?? null;
            $orderingCustomerBic = $identificationDetails->getOrderingCustomerOfiIdentifier() ?? null;
        }

        // According to XSD: one of BankIdentifier or Identifier is always present
        $approvingUnit = '';
        $payConApprovingUnitDetails = $pcd->getPayConApprovingUnitDetails();
        if ($payConApprovingUnitDetails !== null) {
            $approvingUnit = $payConApprovingUnitDetails->getApprovingUnitBankIdentifier()
                ?? $payConApprovingUnitDetails->getApprovingUnitIdentifier()
                ?? '';
        }

        return new BankConfirmationDetails(
            $bankConfirmation->getSessionId(),
            (string)$remittanceId,
            $approvingUnit,
            $pcd->getPayConApprovalTime(),
            $pcd->getPaymentReferenceIdentifier(),
            $pcd->getStatusCode(),
            $referenceIdentifier,
            $orderingCustomerNameAddressText,
            $orderingCustomerIdentifier,
            $orderingCustomerBic
        );
    }
}
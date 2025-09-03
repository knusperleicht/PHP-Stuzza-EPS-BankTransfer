<?php
declare(strict_types=1);

namespace Knusperleicht\EpsBankTransfer\Domain;

use InvalidArgumentException;
use Knusperleicht\EpsBankTransfer\Internal\Generated\Protocol\V26\VitalityCheckDetails as V26Details;
use Knusperleicht\EpsBankTransfer\Internal\Generated\Protocol\V27\VitalityCheckDetails as V27Details;

/**
 * Value object representing details provided by the Scheme Operator during a VitalityCheck callback.
 *
 * Exactly one of remittanceIdentifier (structured) or unstructuredRemittanceIdentifier must be provided.
 * Optionally, orderingCustomerIdentifier can be present for protocol v2.7.
 */
class VitalityCheckDetails
{
    /**
     * @var string|null
     */
    private $remittanceIdentifier;

    /**
     * @var string|null
     */
    private $unstructuredRemittanceIdentifier;

    /**
     * @var string|null
     */
    private $orderingCustomerIdentifier;

    /**
     * Create VitalityCheckDetails ensuring exactly one remittance identifier is provided.
     *
     * @param string|null $remittanceIdentifier Structured remittance reference.
     * @param string|null $unstructuredRemittanceIdentifier Free-text remittance reference.
     * @param string|null $orderingCustomerIdentifier Optional ordering customer identifier (v2.7).
     * @throws InvalidArgumentException When neither or both remittance identifiers are provided.
     */
    public function __construct(
        ?string $remittanceIdentifier = null,
        ?string $unstructuredRemittanceIdentifier = null,
        ?string $orderingCustomerIdentifier = null
    )
    {
        if ($remittanceIdentifier === null && $unstructuredRemittanceIdentifier === null) {
            throw new InvalidArgumentException(
                'VitalityCheckDetails must have either remittanceIdentifier or unstructuredRemittanceIdentifier'
            );
        }
        if ($remittanceIdentifier !== null && $unstructuredRemittanceIdentifier !== null) {
            throw new InvalidArgumentException(
                'VitalityCheckDetails cannot have both remittanceIdentifier and unstructuredRemittanceIdentifier'
            );
        }

        $this->remittanceIdentifier = $remittanceIdentifier;
        $this->unstructuredRemittanceIdentifier = $unstructuredRemittanceIdentifier;
        $this->orderingCustomerIdentifier = $orderingCustomerIdentifier;
    }

    /**
     * @return string|null
     */
    public function getRemittanceIdentifier(): ?string
    {
        return $this->remittanceIdentifier;
    }

    /**
     * @return string|null
     */
    public function getUnstructuredRemittanceIdentifier(): ?string
    {
        return $this->unstructuredRemittanceIdentifier;
    }

    /**
     * @return string|null
     */
    public function getOrderingCustomerIdentifier(): ?string
    {
        return $this->orderingCustomerIdentifier;
    }

    /**
     * @return bool
     */
    public function isStructured(): bool
    {
        return $this->remittanceIdentifier !== null;
    }

    /**
     * @return bool
     */
    public function isUnstructured(): bool
    {
        return $this->unstructuredRemittanceIdentifier !== null;
    }

    /**
     * @param V26Details $vitalityCheckDetails
     * @return self
     */
    public static function fromV26(V26Details $vitalityCheckDetails): self
    {
        return new self(
            $vitalityCheckDetails->getRemittanceIdentifier(),
            $vitalityCheckDetails->getUnstructuredRemittanceIdentifier()
        );
    }

    /**
     * @param V27Details $vitalityCheckDetails
     * @return self
     */
    public static function fromV27(V27Details $vitalityCheckDetails): self
    {
        return new self(
            $vitalityCheckDetails->getRemittanceIdentifier(),
            $vitalityCheckDetails->getUnstructuredRemittanceIdentifier(),
            $vitalityCheckDetails->getOrderingCustomerIdentifier()
        );
    }
}
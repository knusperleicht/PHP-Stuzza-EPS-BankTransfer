<?php

namespace Knusperleicht\EpsBankTransfer\Internal\Generated\Epi\EpiDetails;

/**
 * Class representing EpiDetailsAType
 */
class EpiDetailsAType
{
    /**
     * @var \Knusperleicht\EpsBankTransfer\Internal\Generated\Epi\IdentificationDetails $identificationDetails
     */
    private $identificationDetails = null;

    /**
     * @var \Knusperleicht\EpsBankTransfer\Internal\Generated\Epi\PartyDetails $partyDetails
     */
    private $partyDetails = null;

    /**
     * @var \Knusperleicht\EpsBankTransfer\Internal\Generated\Epi\PaymentInstructionDetails $paymentInstructionDetails
     */
    private $paymentInstructionDetails = null;

    /**
     * Gets as identificationDetails
     *
     * @return \Knusperleicht\EpsBankTransfer\Internal\Generated\Epi\IdentificationDetails
     */
    public function getIdentificationDetails()
    {
        return $this->identificationDetails;
    }

    /**
     * Sets a new identificationDetails
     *
     * @param \Knusperleicht\EpsBankTransfer\Internal\Generated\Epi\IdentificationDetails $identificationDetails
     * @return self
     */
    public function setIdentificationDetails(\Knusperleicht\EpsBankTransfer\Internal\Generated\Epi\IdentificationDetails $identificationDetails)
    {
        $this->identificationDetails = $identificationDetails;
        return $this;
    }

    /**
     * Gets as partyDetails
     *
     * @return \Knusperleicht\EpsBankTransfer\Internal\Generated\Epi\PartyDetails
     */
    public function getPartyDetails()
    {
        return $this->partyDetails;
    }

    /**
     * Sets a new partyDetails
     *
     * @param \Knusperleicht\EpsBankTransfer\Internal\Generated\Epi\PartyDetails $partyDetails
     * @return self
     */
    public function setPartyDetails(\Knusperleicht\EpsBankTransfer\Internal\Generated\Epi\PartyDetails $partyDetails)
    {
        $this->partyDetails = $partyDetails;
        return $this;
    }

    /**
     * Gets as paymentInstructionDetails
     *
     * @return \Knusperleicht\EpsBankTransfer\Internal\Generated\Epi\PaymentInstructionDetails
     */
    public function getPaymentInstructionDetails()
    {
        return $this->paymentInstructionDetails;
    }

    /**
     * Sets a new paymentInstructionDetails
     *
     * @param \Knusperleicht\EpsBankTransfer\Internal\Generated\Epi\PaymentInstructionDetails $paymentInstructionDetails
     * @return self
     */
    public function setPaymentInstructionDetails(\Knusperleicht\EpsBankTransfer\Internal\Generated\Epi\PaymentInstructionDetails $paymentInstructionDetails)
    {
        $this->paymentInstructionDetails = $paymentInstructionDetails;
        return $this;
    }
}


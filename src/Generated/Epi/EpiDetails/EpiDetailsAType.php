<?php

namespace Psa\EpsBankTransfer\Generated\Epi\EpiDetails;

/**
 * Class representing EpiDetailsAType
 */
class EpiDetailsAType
{

    /**
     * @var \Psa\EpsBankTransfer\Generated\Epi\IdentificationDetails $identificationDetails
     */
    private $identificationDetails = null;

    /**
     * @var \Psa\EpsBankTransfer\Generated\Epi\PartyDetails $partyDetails
     */
    private $partyDetails = null;

    /**
     * @var \Psa\EpsBankTransfer\Generated\Epi\PaymentInstructionDetails $paymentInstructionDetails
     */
    private $paymentInstructionDetails = null;

    /**
     * Gets as identificationDetails
     *
     * @return \Psa\EpsBankTransfer\Generated\Epi\IdentificationDetails
     */
    public function getIdentificationDetails()
    {
        return $this->identificationDetails;
    }

    /**
     * Sets a new identificationDetails
     *
     * @param \Psa\EpsBankTransfer\Generated\Epi\IdentificationDetails $identificationDetails
     * @return self
     */
    public function setIdentificationDetails(\Psa\EpsBankTransfer\Generated\Epi\IdentificationDetails $identificationDetails)
    {
        $this->identificationDetails = $identificationDetails;
        return $this;
    }

    /**
     * Gets as partyDetails
     *
     * @return \Psa\EpsBankTransfer\Generated\Epi\PartyDetails
     */
    public function getPartyDetails()
    {
        return $this->partyDetails;
    }

    /**
     * Sets a new partyDetails
     *
     * @param \Psa\EpsBankTransfer\Generated\Epi\PartyDetails $partyDetails
     * @return self
     */
    public function setPartyDetails(\Psa\EpsBankTransfer\Generated\Epi\PartyDetails $partyDetails)
    {
        $this->partyDetails = $partyDetails;
        return $this;
    }

    /**
     * Gets as paymentInstructionDetails
     *
     * @return \Psa\EpsBankTransfer\Generated\Epi\PaymentInstructionDetails
     */
    public function getPaymentInstructionDetails()
    {
        return $this->paymentInstructionDetails;
    }

    /**
     * Sets a new paymentInstructionDetails
     *
     * @param \Psa\EpsBankTransfer\Generated\Epi\PaymentInstructionDetails $paymentInstructionDetails
     * @return self
     */
    public function setPaymentInstructionDetails(\Psa\EpsBankTransfer\Generated\Epi\PaymentInstructionDetails $paymentInstructionDetails)
    {
        $this->paymentInstructionDetails = $paymentInstructionDetails;
        return $this;
    }


}


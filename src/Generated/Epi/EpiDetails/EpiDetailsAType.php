<?php

namespace Externet\EpsBankTransfer\Generated\Epi\EpiDetails;

/**
 * Class representing EpiDetailsAType
 */
class EpiDetailsAType
{

    /**
     * @var \Externet\EpsBankTransfer\Generated\Epi\IdentificationDetails $identificationDetails
     */
    private $identificationDetails = null;

    /**
     * @var \Externet\EpsBankTransfer\Generated\Epi\PartyDetails $partyDetails
     */
    private $partyDetails = null;

    /**
     * @var \Externet\EpsBankTransfer\Generated\Epi\PaymentInstructionDetails $paymentInstructionDetails
     */
    private $paymentInstructionDetails = null;

    /**
     * Gets as identificationDetails
     *
     * @return \Externet\EpsBankTransfer\Generated\Epi\IdentificationDetails
     */
    public function getIdentificationDetails()
    {
        return $this->identificationDetails;
    }

    /**
     * Sets a new identificationDetails
     *
     * @param \Externet\EpsBankTransfer\Generated\Epi\IdentificationDetails $identificationDetails
     * @return self
     */
    public function setIdentificationDetails(\Externet\EpsBankTransfer\Generated\Epi\IdentificationDetails $identificationDetails)
    {
        $this->identificationDetails = $identificationDetails;
        return $this;
    }

    /**
     * Gets as partyDetails
     *
     * @return \Externet\EpsBankTransfer\Generated\Epi\PartyDetails
     */
    public function getPartyDetails()
    {
        return $this->partyDetails;
    }

    /**
     * Sets a new partyDetails
     *
     * @param \Externet\EpsBankTransfer\Generated\Epi\PartyDetails $partyDetails
     * @return self
     */
    public function setPartyDetails(\Externet\EpsBankTransfer\Generated\Epi\PartyDetails $partyDetails)
    {
        $this->partyDetails = $partyDetails;
        return $this;
    }

    /**
     * Gets as paymentInstructionDetails
     *
     * @return \Externet\EpsBankTransfer\Generated\Epi\PaymentInstructionDetails
     */
    public function getPaymentInstructionDetails()
    {
        return $this->paymentInstructionDetails;
    }

    /**
     * Sets a new paymentInstructionDetails
     *
     * @param \Externet\EpsBankTransfer\Generated\Epi\PaymentInstructionDetails $paymentInstructionDetails
     * @return self
     */
    public function setPaymentInstructionDetails(\Externet\EpsBankTransfer\Generated\Epi\PaymentInstructionDetails $paymentInstructionDetails)
    {
        $this->paymentInstructionDetails = $paymentInstructionDetails;
        return $this;
    }


}


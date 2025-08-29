<?php

namespace Psa\EpsBankTransfer\Generated\Epi\PartyDetails;

/**
 * Class representing PartyDetailsAType
 */
class PartyDetailsAType
{

    /**
     * @var \Psa\EpsBankTransfer\Generated\Epi\BfiPartyDetails $bfiPartyDetails
     */
    private $bfiPartyDetails = null;

    /**
     * @var \Psa\EpsBankTransfer\Generated\Epi\BeneficiaryPartyDetails $beneficiaryPartyDetails
     */
    private $beneficiaryPartyDetails = null;

    /**
     * Gets as bfiPartyDetails
     *
     * @return \Psa\EpsBankTransfer\Generated\Epi\BfiPartyDetails
     */
    public function getBfiPartyDetails()
    {
        return $this->bfiPartyDetails;
    }

    /**
     * Sets a new bfiPartyDetails
     *
     * @param \Psa\EpsBankTransfer\Generated\Epi\BfiPartyDetails $bfiPartyDetails
     * @return self
     */
    public function setBfiPartyDetails(\Psa\EpsBankTransfer\Generated\Epi\BfiPartyDetails $bfiPartyDetails)
    {
        $this->bfiPartyDetails = $bfiPartyDetails;
        return $this;
    }

    /**
     * Gets as beneficiaryPartyDetails
     *
     * @return \Psa\EpsBankTransfer\Generated\Epi\BeneficiaryPartyDetails
     */
    public function getBeneficiaryPartyDetails()
    {
        return $this->beneficiaryPartyDetails;
    }

    /**
     * Sets a new beneficiaryPartyDetails
     *
     * @param \Psa\EpsBankTransfer\Generated\Epi\BeneficiaryPartyDetails $beneficiaryPartyDetails
     * @return self
     */
    public function setBeneficiaryPartyDetails(\Psa\EpsBankTransfer\Generated\Epi\BeneficiaryPartyDetails $beneficiaryPartyDetails)
    {
        $this->beneficiaryPartyDetails = $beneficiaryPartyDetails;
        return $this;
    }


}


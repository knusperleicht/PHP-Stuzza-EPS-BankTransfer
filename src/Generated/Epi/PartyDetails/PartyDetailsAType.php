<?php

namespace Externet\EpsBankTransfer\Generated\Epi\PartyDetails;

/**
 * Class representing PartyDetailsAType
 */
class PartyDetailsAType
{

    /**
     * @var \Externet\EpsBankTransfer\Generated\Epi\BfiPartyDetails $bfiPartyDetails
     */
    private $bfiPartyDetails = null;

    /**
     * @var \Externet\EpsBankTransfer\Generated\Epi\BeneficiaryPartyDetails $beneficiaryPartyDetails
     */
    private $beneficiaryPartyDetails = null;

    /**
     * Gets as bfiPartyDetails
     *
     * @return \Externet\EpsBankTransfer\Generated\Epi\BfiPartyDetails
     */
    public function getBfiPartyDetails()
    {
        return $this->bfiPartyDetails;
    }

    /**
     * Sets a new bfiPartyDetails
     *
     * @param \Externet\EpsBankTransfer\Generated\Epi\BfiPartyDetails $bfiPartyDetails
     * @return self
     */
    public function setBfiPartyDetails(\Externet\EpsBankTransfer\Generated\Epi\BfiPartyDetails $bfiPartyDetails)
    {
        $this->bfiPartyDetails = $bfiPartyDetails;
        return $this;
    }

    /**
     * Gets as beneficiaryPartyDetails
     *
     * @return \Externet\EpsBankTransfer\Generated\Epi\BeneficiaryPartyDetails
     */
    public function getBeneficiaryPartyDetails()
    {
        return $this->beneficiaryPartyDetails;
    }

    /**
     * Sets a new beneficiaryPartyDetails
     *
     * @param \Externet\EpsBankTransfer\Generated\Epi\BeneficiaryPartyDetails $beneficiaryPartyDetails
     * @return self
     */
    public function setBeneficiaryPartyDetails(\Externet\EpsBankTransfer\Generated\Epi\BeneficiaryPartyDetails $beneficiaryPartyDetails)
    {
        $this->beneficiaryPartyDetails = $beneficiaryPartyDetails;
        return $this;
    }


}


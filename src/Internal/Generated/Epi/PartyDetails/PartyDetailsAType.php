<?php

namespace Knusperleicht\EpsBankTransfer\Internal\Generated\Epi\PartyDetails;

/**
 * Class representing PartyDetailsAType
 */
class PartyDetailsAType
{
    /**
     * @var \Knusperleicht\EpsBankTransfer\Internal\Generated\Epi\BfiPartyDetails $bfiPartyDetails
     */
    private $bfiPartyDetails = null;

    /**
     * @var \Knusperleicht\EpsBankTransfer\Internal\Generated\Epi\BeneficiaryPartyDetails $beneficiaryPartyDetails
     */
    private $beneficiaryPartyDetails = null;

    /**
     * Gets as bfiPartyDetails
     *
     * @return \Knusperleicht\EpsBankTransfer\Internal\Generated\Epi\BfiPartyDetails
     */
    public function getBfiPartyDetails()
    {
        return $this->bfiPartyDetails;
    }

    /**
     * Sets a new bfiPartyDetails
     *
     * @param \Knusperleicht\EpsBankTransfer\Internal\Generated\Epi\BfiPartyDetails $bfiPartyDetails
     * @return self
     */
    public function setBfiPartyDetails(\Knusperleicht\EpsBankTransfer\Internal\Generated\Epi\BfiPartyDetails $bfiPartyDetails)
    {
        $this->bfiPartyDetails = $bfiPartyDetails;
        return $this;
    }

    /**
     * Gets as beneficiaryPartyDetails
     *
     * @return \Knusperleicht\EpsBankTransfer\Internal\Generated\Epi\BeneficiaryPartyDetails
     */
    public function getBeneficiaryPartyDetails()
    {
        return $this->beneficiaryPartyDetails;
    }

    /**
     * Sets a new beneficiaryPartyDetails
     *
     * @param \Knusperleicht\EpsBankTransfer\Internal\Generated\Epi\BeneficiaryPartyDetails $beneficiaryPartyDetails
     * @return self
     */
    public function setBeneficiaryPartyDetails(\Knusperleicht\EpsBankTransfer\Internal\Generated\Epi\BeneficiaryPartyDetails $beneficiaryPartyDetails)
    {
        $this->beneficiaryPartyDetails = $beneficiaryPartyDetails;
        return $this;
    }
}


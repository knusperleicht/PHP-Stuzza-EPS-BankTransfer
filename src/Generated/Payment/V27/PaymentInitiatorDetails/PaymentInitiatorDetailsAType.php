<?php

namespace Psa\EpsBankTransfer\Generated\Payment\V27\PaymentInitiatorDetails;

/**
 * Class representing PaymentInitiatorDetailsAType
 */
class PaymentInitiatorDetailsAType
{

    /**
     * @var \Psa\EpsBankTransfer\Generated\Epi\EpiDetails $epiDetails
     */
    private $epiDetails = null;

    /**
     * @var \Psa\EpsBankTransfer\Generated\AustrianRules\AustrianRulesDetails $austrianRulesDetails
     */
    private $austrianRulesDetails = null;

    /**
     * Gets as epiDetails
     *
     * @return \Psa\EpsBankTransfer\Generated\Epi\EpiDetails
     */
    public function getEpiDetails()
    {
        return $this->epiDetails;
    }

    /**
     * Sets a new epiDetails
     *
     * @param \Psa\EpsBankTransfer\Generated\Epi\EpiDetails $epiDetails
     * @return self
     */
    public function setEpiDetails(\Psa\EpsBankTransfer\Generated\Epi\EpiDetails $epiDetails)
    {
        $this->epiDetails = $epiDetails;
        return $this;
    }

    /**
     * Gets as austrianRulesDetails
     *
     * @return \Psa\EpsBankTransfer\Generated\AustrianRules\AustrianRulesDetails
     */
    public function getAustrianRulesDetails()
    {
        return $this->austrianRulesDetails;
    }

    /**
     * Sets a new austrianRulesDetails
     *
     * @param \Psa\EpsBankTransfer\Generated\AustrianRules\AustrianRulesDetails $austrianRulesDetails
     * @return self
     */
    public function setAustrianRulesDetails(?\Psa\EpsBankTransfer\Generated\AustrianRules\AustrianRulesDetails $austrianRulesDetails = null)
    {
        $this->austrianRulesDetails = $austrianRulesDetails;
        return $this;
    }


}


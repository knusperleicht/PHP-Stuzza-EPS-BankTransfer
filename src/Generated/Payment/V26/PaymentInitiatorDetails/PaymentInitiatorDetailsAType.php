<?php

namespace Externet\EpsBankTransfer\Generated\Payment\V26\PaymentInitiatorDetails;

/**
 * Class representing PaymentInitiatorDetailsAType
 */
class PaymentInitiatorDetailsAType
{

    /**
     * @var \Externet\EpsBankTransfer\Generated\Epi\EpiDetails $epiDetails
     */
    private $epiDetails = null;

    /**
     * @var \Externet\EpsBankTransfer\Generated\AustrianRules\AustrianRulesDetails $austrianRulesDetails
     */
    private $austrianRulesDetails = null;

    /**
     * Gets as epiDetails
     *
     * @return \Externet\EpsBankTransfer\Generated\Epi\EpiDetails
     */
    public function getEpiDetails()
    {
        return $this->epiDetails;
    }

    /**
     * Sets a new epiDetails
     *
     * @param \Externet\EpsBankTransfer\Generated\Epi\EpiDetails $epiDetails
     * @return self
     */
    public function setEpiDetails(\Externet\EpsBankTransfer\Generated\Epi\EpiDetails $epiDetails)
    {
        $this->epiDetails = $epiDetails;
        return $this;
    }

    /**
     * Gets as austrianRulesDetails
     *
     * @return \Externet\EpsBankTransfer\Generated\AustrianRules\AustrianRulesDetails
     */
    public function getAustrianRulesDetails()
    {
        return $this->austrianRulesDetails;
    }

    /**
     * Sets a new austrianRulesDetails
     *
     * @param \Externet\EpsBankTransfer\Generated\AustrianRules\AustrianRulesDetails $austrianRulesDetails
     * @return self
     */
    public function setAustrianRulesDetails(?\Externet\EpsBankTransfer\Generated\AustrianRules\AustrianRulesDetails $austrianRulesDetails = null)
    {
        $this->austrianRulesDetails = $austrianRulesDetails;
        return $this;
    }


}


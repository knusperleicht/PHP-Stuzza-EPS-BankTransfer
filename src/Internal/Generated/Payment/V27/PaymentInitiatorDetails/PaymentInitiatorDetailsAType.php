<?php

namespace Knusperleicht\EpsBankTransfer\Internal\Generated\Payment\V27\PaymentInitiatorDetails;

/**
 * Class representing PaymentInitiatorDetailsAType
 */
class PaymentInitiatorDetailsAType
{
    /**
     * @var \Knusperleicht\EpsBankTransfer\Internal\Generated\Epi\EpiDetails $epiDetails
     */
    private $epiDetails = null;

    /**
     * @var \Knusperleicht\EpsBankTransfer\Internal\Generated\AustrianRules\AustrianRulesDetails $austrianRulesDetails
     */
    private $austrianRulesDetails = null;

    /**
     * Gets as epiDetails
     *
     * @return \Knusperleicht\EpsBankTransfer\Internal\Generated\Epi\EpiDetails
     */
    public function getEpiDetails()
    {
        return $this->epiDetails;
    }

    /**
     * Sets a new epiDetails
     *
     * @param \Knusperleicht\EpsBankTransfer\Internal\Generated\Epi\EpiDetails $epiDetails
     * @return self
     */
    public function setEpiDetails(\Knusperleicht\EpsBankTransfer\Internal\Generated\Epi\EpiDetails $epiDetails)
    {
        $this->epiDetails = $epiDetails;
        return $this;
    }

    /**
     * Gets as austrianRulesDetails
     *
     * @return \Knusperleicht\EpsBankTransfer\Internal\Generated\AustrianRules\AustrianRulesDetails
     */
    public function getAustrianRulesDetails()
    {
        return $this->austrianRulesDetails;
    }

    /**
     * Sets a new austrianRulesDetails
     *
     * @param \Knusperleicht\EpsBankTransfer\Internal\Generated\AustrianRules\AustrianRulesDetails $austrianRulesDetails
     * @return self
     */
    public function setAustrianRulesDetails(?\Knusperleicht\EpsBankTransfer\Internal\Generated\AustrianRules\AustrianRulesDetails $austrianRulesDetails = null)
    {
        $this->austrianRulesDetails = $austrianRulesDetails;
        return $this;
    }
}


<?php
declare(strict_types=1);

namespace Externet\EpsBankTransfer;

class EpsProtocolDetails
{
    /** @var BankResponseDetails Contains the bank response details */
    public $bankResponseDetails;

    public function __construct(BankResponseDetails $bankResponseDetails)
    {
        $this->bankResponseDetails = $bankResponseDetails;
    }

}
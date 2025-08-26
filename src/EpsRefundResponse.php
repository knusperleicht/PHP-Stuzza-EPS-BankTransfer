<?php
declare(strict_types=1);

namespace Externet\EpsBankTransfer;

use Exception;

class EpsRefundResponse
{
    /** @var string Response status code (max length 3) */
    public $statusCode;

    /** @var string|null Error message (optional, max length 255) */
    public $errorMsg;
    
}
<?php
declare(strict_types=1);

namespace Externet\EpsBankTransfer;

class EpsRefundResponse
{
    /** @var string Response status code (max length 3) */
    public $statusCode;

    /** @var string|null Error message (optional, max length 255) */
    public $errorMsg;

    public function __construct(string $statusCode, ?string $errorMsg = null)
    {
        $this->statusCode = $statusCode;
        $this->errorMsg = $errorMsg;
    }
}
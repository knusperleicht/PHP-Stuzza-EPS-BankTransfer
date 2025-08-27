<?php

namespace Externet\EpsBankTransfer\Exceptions;

use Exception;

class ShopResponseException extends Exception
{
    public function getShopResponseErrorMessage(): string
    {
        return $this->getMessage();
    }
}
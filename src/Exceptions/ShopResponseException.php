<?php

namespace Externet\EpsBankTransfer\Exceptions;

use Exception;

class ShopResponseException extends Exception
{
    public function GetShopResponseErrorMessage(): string
    {
        return $this->getMessage();
    }
}
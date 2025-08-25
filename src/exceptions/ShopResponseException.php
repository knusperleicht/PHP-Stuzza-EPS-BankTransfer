<?php

namespace at\externet\eps_bank_transfer\exceptions;

use Exception;

class ShopResponseException extends Exception
{
    public function GetShopResponseErrorMessage(): string
    {
        return $this->getMessage();
    }
}
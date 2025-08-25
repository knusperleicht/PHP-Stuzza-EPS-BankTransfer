<?php

namespace at\externet\eps_bank_transfer\exceptions;

class XmlValidationException extends ShopResponseException
{
    public function GetShopResponseErrorMessage(): string
    {
        return 'Error occurred during XML validation';
    }
}
<?php

namespace Externet\EpsBankTransfer\Exceptions;

class XmlValidationException extends ShopResponseException
{
    public function GetShopResponseErrorMessage(): string
    {
        return 'Error occurred during XML validation';
    }
}
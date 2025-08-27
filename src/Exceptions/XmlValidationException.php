<?php

namespace Externet\EpsBankTransfer\Exceptions;

class XmlValidationException extends ShopResponseException
{
    public function getShopResponseErrorMessage(): string
    {
        return 'Error occurred during XML validation';
    }
}
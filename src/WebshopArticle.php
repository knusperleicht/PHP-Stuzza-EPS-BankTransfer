<?php
declare(strict_types=1);

namespace Externet\EpsBankTransfer;

use Externet\EpsBankTransfer\Utilities\MoneyFormatter;

class WebshopArticle
{

    /** @var string name */
    public $Name;
    
    /** @var number of items */
    public $Count;
    
    /** @var string representation of price */
    public $Price;

    /**
     * 
     * @param string $name item name
     * @param int $count number of items
     * @param int $price price in cents
     */
    public function __construct(string $name, int $count, int $price)
    {
        $this->Name = $name;
        $this->Count = $count;
        $this->SetPrice($price);
    }

    /**
     * 
     * @param int $value in cents
     */
    public function SetPrice(int $value)
    {
        $this->Price = MoneyFormatter::formatXsdDecimal($value);
    }
}


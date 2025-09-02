<?php

namespace Knusperleicht\EpsBankTransfer\Internal\Generated\Protocol\V27\WebshopDetails;

/**
 * Class representing WebshopDetailsAType
 */
class WebshopDetailsAType
{
    /**
     * @var \Knusperleicht\EpsBankTransfer\Internal\Generated\Protocol\V27\WebshopArticle[] $webshopArticle
     */
    private $webshopArticle = [
        
    ];

    /**
     * Adds as webshopArticle
     *
     * @return self
     * @param \Knusperleicht\EpsBankTransfer\Internal\Generated\Protocol\V27\WebshopArticle $webshopArticle
     */
    public function addToWebshopArticle(\Knusperleicht\EpsBankTransfer\Internal\Generated\Protocol\V27\WebshopArticle $webshopArticle)
    {
        $this->webshopArticle[] = $webshopArticle;
        return $this;
    }

    /**
     * isset webshopArticle
     *
     * @param int|string $index
     * @return bool
     */
    public function issetWebshopArticle($index)
    {
        return isset($this->webshopArticle[$index]);
    }

    /**
     * unset webshopArticle
     *
     * @param int|string $index
     * @return void
     */
    public function unsetWebshopArticle($index)
    {
        unset($this->webshopArticle[$index]);
    }

    /**
     * Gets as webshopArticle
     *
     * @return \Knusperleicht\EpsBankTransfer\Internal\Generated\Protocol\V27\WebshopArticle[]
     */
    public function getWebshopArticle()
    {
        return $this->webshopArticle;
    }

    /**
     * Sets a new webshopArticle
     *
     * @param \Knusperleicht\EpsBankTransfer\Internal\Generated\Protocol\V27\WebshopArticle[] $webshopArticle
     * @return self
     */
    public function setWebshopArticle(array $webshopArticle)
    {
        $this->webshopArticle = $webshopArticle;
        return $this;
    }
}


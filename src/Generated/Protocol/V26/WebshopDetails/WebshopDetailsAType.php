<?php

namespace Externet\EpsBankTransfer\Generated\Protocol\V26\WebshopDetails;

/**
 * Class representing WebshopDetailsAType
 */
class WebshopDetailsAType
{

    /**
     * @var \Externet\EpsBankTransfer\Generated\Protocol\V26\WebshopArticle[] $webshopArticle
     */
    private $webshopArticle = [
        
    ];

    /**
     * Adds as webshopArticle
     *
     * @return self
     * @param \Externet\EpsBankTransfer\Generated\Protocol\V26\WebshopArticle $webshopArticle
     */
    public function addToWebshopArticle(\Externet\EpsBankTransfer\Generated\Protocol\V26\WebshopArticle $webshopArticle)
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
     * @return \Externet\EpsBankTransfer\Generated\Protocol\V26\WebshopArticle[]
     */
    public function getWebshopArticle()
    {
        return $this->webshopArticle;
    }

    /**
     * Sets a new webshopArticle
     *
     * @param \Externet\EpsBankTransfer\Generated\Protocol\V26\WebshopArticle[] $webshopArticle
     * @return self
     */
    public function setWebshopArticle(array $webshopArticle)
    {
        $this->webshopArticle = $webshopArticle;
        return $this;
    }


}


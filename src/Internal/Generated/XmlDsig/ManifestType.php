<?php

namespace Knusperleicht\EpsBankTransfer\Internal\Generated\XmlDsig;

/**
 * Class representing ManifestType
 *
 *
 * XSD Type: ManifestType
 */
class ManifestType
{
    /**
     * @var string $id
     */
    private $id = null;

    /**
     * @var \Knusperleicht\EpsBankTransfer\Internal\Generated\XmlDsig\Reference[] $reference
     */
    private $reference = [
        
    ];

    /**
     * Gets as id
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Sets a new id
     *
     * @param string $id
     * @return self
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Adds as reference
     *
     * @return self
     * @param \Knusperleicht\EpsBankTransfer\Internal\Generated\XmlDsig\Reference $reference
     */
    public function addToReference(\Knusperleicht\EpsBankTransfer\Internal\Generated\XmlDsig\Reference $reference)
    {
        $this->reference[] = $reference;
        return $this;
    }

    /**
     * isset reference
     *
     * @param int|string $index
     * @return bool
     */
    public function issetReference($index)
    {
        return isset($this->reference[$index]);
    }

    /**
     * unset reference
     *
     * @param int|string $index
     * @return void
     */
    public function unsetReference($index)
    {
        unset($this->reference[$index]);
    }

    /**
     * Gets as reference
     *
     * @return \Knusperleicht\EpsBankTransfer\Internal\Generated\XmlDsig\Reference[]
     */
    public function getReference()
    {
        return $this->reference;
    }

    /**
     * Sets a new reference
     *
     * @param \Knusperleicht\EpsBankTransfer\Internal\Generated\XmlDsig\Reference[] $reference
     * @return self
     */
    public function setReference(array $reference)
    {
        $this->reference = $reference;
        return $this;
    }
}


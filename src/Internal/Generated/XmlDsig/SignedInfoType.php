<?php

namespace Knusperleicht\EpsBankTransfer\Internal\Generated\XmlDsig;

/**
 * Class representing SignedInfoType
 *
 *
 * XSD Type: SignedInfoType
 */
class SignedInfoType
{
    /**
     * @var string $id
     */
    private $id = null;

    /**
     * @var \Knusperleicht\EpsBankTransfer\Internal\Generated\XmlDsig\CanonicalizationMethod $canonicalizationMethod
     */
    private $canonicalizationMethod = null;

    /**
     * @var \Knusperleicht\EpsBankTransfer\Internal\Generated\XmlDsig\SignatureMethod $signatureMethod
     */
    private $signatureMethod = null;

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
     * Gets as canonicalizationMethod
     *
     * @return \Knusperleicht\EpsBankTransfer\Internal\Generated\XmlDsig\CanonicalizationMethod
     */
    public function getCanonicalizationMethod()
    {
        return $this->canonicalizationMethod;
    }

    /**
     * Sets a new canonicalizationMethod
     *
     * @param \Knusperleicht\EpsBankTransfer\Internal\Generated\XmlDsig\CanonicalizationMethod $canonicalizationMethod
     * @return self
     */
    public function setCanonicalizationMethod(\Knusperleicht\EpsBankTransfer\Internal\Generated\XmlDsig\CanonicalizationMethod $canonicalizationMethod)
    {
        $this->canonicalizationMethod = $canonicalizationMethod;
        return $this;
    }

    /**
     * Gets as signatureMethod
     *
     * @return \Knusperleicht\EpsBankTransfer\Internal\Generated\XmlDsig\SignatureMethod
     */
    public function getSignatureMethod()
    {
        return $this->signatureMethod;
    }

    /**
     * Sets a new signatureMethod
     *
     * @param \Knusperleicht\EpsBankTransfer\Internal\Generated\XmlDsig\SignatureMethod $signatureMethod
     * @return self
     */
    public function setSignatureMethod(\Knusperleicht\EpsBankTransfer\Internal\Generated\XmlDsig\SignatureMethod $signatureMethod)
    {
        $this->signatureMethod = $signatureMethod;
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


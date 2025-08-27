<?php

namespace Externet\EpsBankTransfer\Generated\XmlDsig;

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
     * @var \Externet\EpsBankTransfer\Generated\XmlDsig\CanonicalizationMethod $canonicalizationMethod
     */
    private $canonicalizationMethod = null;

    /**
     * @var \Externet\EpsBankTransfer\Generated\XmlDsig\SignatureMethod $signatureMethod
     */
    private $signatureMethod = null;

    /**
     * @var \Externet\EpsBankTransfer\Generated\XmlDsig\Reference[] $reference
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
     * @return \Externet\EpsBankTransfer\Generated\XmlDsig\CanonicalizationMethod
     */
    public function getCanonicalizationMethod()
    {
        return $this->canonicalizationMethod;
    }

    /**
     * Sets a new canonicalizationMethod
     *
     * @param \Externet\EpsBankTransfer\Generated\XmlDsig\CanonicalizationMethod $canonicalizationMethod
     * @return self
     */
    public function setCanonicalizationMethod(\Externet\EpsBankTransfer\Generated\XmlDsig\CanonicalizationMethod $canonicalizationMethod)
    {
        $this->canonicalizationMethod = $canonicalizationMethod;
        return $this;
    }

    /**
     * Gets as signatureMethod
     *
     * @return \Externet\EpsBankTransfer\Generated\XmlDsig\SignatureMethod
     */
    public function getSignatureMethod()
    {
        return $this->signatureMethod;
    }

    /**
     * Sets a new signatureMethod
     *
     * @param \Externet\EpsBankTransfer\Generated\XmlDsig\SignatureMethod $signatureMethod
     * @return self
     */
    public function setSignatureMethod(\Externet\EpsBankTransfer\Generated\XmlDsig\SignatureMethod $signatureMethod)
    {
        $this->signatureMethod = $signatureMethod;
        return $this;
    }

    /**
     * Adds as reference
     *
     * @return self
     * @param \Externet\EpsBankTransfer\Generated\XmlDsig\Reference $reference
     */
    public function addToReference(\Externet\EpsBankTransfer\Generated\XmlDsig\Reference $reference)
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
     * @return \Externet\EpsBankTransfer\Generated\XmlDsig\Reference[]
     */
    public function getReference()
    {
        return $this->reference;
    }

    /**
     * Sets a new reference
     *
     * @param \Externet\EpsBankTransfer\Generated\XmlDsig\Reference[] $reference
     * @return self
     */
    public function setReference(array $reference)
    {
        $this->reference = $reference;
        return $this;
    }


}


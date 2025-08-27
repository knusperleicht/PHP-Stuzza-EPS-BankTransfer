<?php

namespace Externet\EpsBankTransfer\Generated\XmlDsig;

/**
 * Class representing SignatureType
 *
 *
 * XSD Type: SignatureType
 */
class SignatureType
{

    /**
     * @var string $id
     */
    private $id = null;

    /**
     * @var \Externet\EpsBankTransfer\Generated\XmlDsig\SignedInfo $signedInfo
     */
    private $signedInfo = null;

    /**
     * @var \Externet\EpsBankTransfer\Generated\XmlDsig\SignatureValue $signatureValue
     */
    private $signatureValue = null;

    /**
     * @var \Externet\EpsBankTransfer\Generated\XmlDsig\KeyInfo $keyInfo
     */
    private $keyInfo = null;

    /**
     * @var \Externet\EpsBankTransfer\Generated\XmlDsig\ObjectXsd[] $object
     */
    private $object = [
        
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
     * Gets as signedInfo
     *
     * @return \Externet\EpsBankTransfer\Generated\XmlDsig\SignedInfo
     */
    public function getSignedInfo()
    {
        return $this->signedInfo;
    }

    /**
     * Sets a new signedInfo
     *
     * @param \Externet\EpsBankTransfer\Generated\XmlDsig\SignedInfo $signedInfo
     * @return self
     */
    public function setSignedInfo(\Externet\EpsBankTransfer\Generated\XmlDsig\SignedInfo $signedInfo)
    {
        $this->signedInfo = $signedInfo;
        return $this;
    }

    /**
     * Gets as signatureValue
     *
     * @return \Externet\EpsBankTransfer\Generated\XmlDsig\SignatureValue
     */
    public function getSignatureValue()
    {
        return $this->signatureValue;
    }

    /**
     * Sets a new signatureValue
     *
     * @param \Externet\EpsBankTransfer\Generated\XmlDsig\SignatureValue $signatureValue
     * @return self
     */
    public function setSignatureValue(\Externet\EpsBankTransfer\Generated\XmlDsig\SignatureValue $signatureValue)
    {
        $this->signatureValue = $signatureValue;
        return $this;
    }

    /**
     * Gets as keyInfo
     *
     * @return \Externet\EpsBankTransfer\Generated\XmlDsig\KeyInfo
     */
    public function getKeyInfo()
    {
        return $this->keyInfo;
    }

    /**
     * Sets a new keyInfo
     *
     * @param \Externet\EpsBankTransfer\Generated\XmlDsig\KeyInfo $keyInfo
     * @return self
     */
    public function setKeyInfo(?\Externet\EpsBankTransfer\Generated\XmlDsig\KeyInfo $keyInfo = null)
    {
        $this->keyInfo = $keyInfo;
        return $this;
    }

    /**
     * Adds as object
     *
     * @return self
     * @param \Externet\EpsBankTransfer\Generated\XmlDsig\ObjectXsd $object
     */
    public function addToObject(\Externet\EpsBankTransfer\Generated\XmlDsig\ObjectXsd $object)
    {
        $this->object[] = $object;
        return $this;
    }

    /**
     * isset object
     *
     * @param int|string $index
     * @return bool
     */
    public function issetObject($index)
    {
        return isset($this->object[$index]);
    }

    /**
     * unset object
     *
     * @param int|string $index
     * @return void
     */
    public function unsetObject($index)
    {
        unset($this->object[$index]);
    }

    /**
     * Gets as object
     *
     * @return \Externet\EpsBankTransfer\Generated\XmlDsig\ObjectXsd[]
     */
    public function getObject()
    {
        return $this->object;
    }

    /**
     * Sets a new object
     *
     * @param \Externet\EpsBankTransfer\Generated\XmlDsig\ObjectXsd[] $object
     * @return self
     */
    public function setObject(array $object = null)
    {
        $this->object = $object;
        return $this;
    }


}


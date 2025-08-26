<?php
declare(strict_types=1);

namespace Externet\EpsBankTransfer;

use DOMDocument;
use Exception;
use SimpleXMLElement;

class EpsXmlElement
{
    private $simpleXml;

    /**
     * @throws Exception
     */
    public function __construct($data, $options = 0, $data_is_url = false, $ns = "", $is_prefix = false)
    {
        if (is_a($data, "SimpleXMLElement")) {
            $this->simpleXml = $data;
        } else {
            $this->simpleXml = new SimpleXMLElement($data, $options, $data_is_url, $ns, $is_prefix);
        }
    }

    /**
     *
     * @param string $rootNode
     * @return EpsXmlElement element
     * @throws Exception
     */
    public static function createEmptySimpleXml(string $rootNode): EpsXmlElement
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?><' . $rootNode . '/>';
        return new EpsXmlElement($xml);
    }

    public function __get($name)
    {
        $ret = $this->simpleXml->$name;
        $ename = $ret->getName();
        if ($ename == "") {
            $ret = $this->simpleXml->xpath('eps:' . $name);
        }
        return new self($ret);
    }

    public function __call($name, $arguments)
    {
        return call_user_func_array(array($this->simpleXml, $name), $arguments);
    }

    public function addChild($name, $value = null, $namespace = null)
    {
        $child = $this->simpleXml->addChild($name, $value, $namespace);
        return new self($child);
    }

    public function addChildExt($name, $value = null, $namespaceAlias = null): EpsXmlElement
    {
        $ns = $this->getDocNamespaces();
        $namespace = $namespaceAlias;
        if (array_key_exists($namespaceAlias, $ns)) {
            $name = $namespaceAlias . ':' . $name;
            $namespace = $ns[$namespace];
        }
        return $this->addChild($name, $value, $namespace);
    }

    public function asXML($filename = null)
    {
        $dom = new DOMDocument();
        $dom->loadXML($this->simpleXml->asXML());
        $dom->formatOutput = true;
        if ($filename == null) {
            return $dom->saveXML();
        }
        return $dom->save($filename);
    }
}
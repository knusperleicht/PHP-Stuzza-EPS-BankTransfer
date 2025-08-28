<?php
declare(strict_types=1);

namespace Externet\EpsBankTransfer\Serializer;

use JMS\Serializer\GraphNavigatorInterface;
use JMS\Serializer\Metadata\ClassMetadata;
use JMS\Serializer\Metadata\PropertyMetadata;
use JMS\Serializer\Visitor\SerializationVisitorInterface;
use JMS\Serializer\XmlSerializationVisitor;

class NoCdataXmlSerializationVisitor implements SerializationVisitorInterface
{
    /** @var SerializationVisitorInterface */
    private $inner;

    public function __construct(SerializationVisitorInterface $inner)
    {
        $this->inner = $inner;
    }

    public function visitString(string $data, array $type)
    {
        return $this->inner->getDocument()->createTextNode((string) $data);
    }

    public function visitNull($data, array $type) { return $this->inner->visitNull($data, $type); }
    public function visitBoolean(bool $data, array $type) { return $this->inner->visitBoolean($data, $type); }
    public function visitInteger(int $data, array $type) { return $this->inner->visitInteger($data, $type); }
    public function visitDouble(float $data, array $type) { return $this->inner->visitDouble($data, $type); }
    public function visitArray(array $data, array $type): void { $this->inner->visitArray($data, $type); }
    public function startVisitingObject(ClassMetadata $metadata, object $data, array $type): void { $this->inner->startVisitingObject($metadata, $data, $type); }
    public function visitProperty(PropertyMetadata $metadata, $v): void { $this->inner->visitProperty($metadata, $v); }
    public function endVisitingObject(ClassMetadata $metadata, object $data, array $type): void { $this->inner->endVisitingObject($metadata, $data, $type); }
    public function getResult($data) { return $this->inner->getResult($data); }
    public function prepare($data) { return $this->inner->prepare($data); }

    public function getDocument() {
        return $this->inner->getDocument();
    }

    public function setNavigator(GraphNavigatorInterface $navigator): void
    {
        $this->inner->setNavigator($navigator);
    }
}
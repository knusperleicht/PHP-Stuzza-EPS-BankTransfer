<?php
declare(strict_types=1);

namespace Externet\EpsBankTransfer\Serializer;

use JMS\Serializer\SerializerBuilder;
use JMS\Serializer\SerializerInterface;

class SerializerFactory
{
    /** @var SerializerInterface|null */
    private static $instance;

    public static function create(): SerializerInterface
    {
        if (self::$instance instanceof SerializerInterface) {
            return self::$instance;
        }

        $projectRoot = dirname(__DIR__, 2);

        self::$instance = SerializerBuilder::create()
            ->setSerializationVisitor('xml', new NoCdataXmlSerializationVisitorFactory())

            ->addMetadataDir($projectRoot . '/config/serializer/Protocol/V26', 'Externet\\EpsBankTransfer\\Generated\\Protocol\\V26')
            ->addMetadataDir($projectRoot . '/config/serializer/Protocol/V27', 'Externet\\EpsBankTransfer\\Generated\\Protocol\\V27')
            ->addMetadataDir($projectRoot . '/config/serializer/Payment/V26', 'Externet\\EpsBankTransfer\\Generated\\Payment\\V26')
            ->addMetadataDir($projectRoot . '/config/serializer/Payment/V27', 'Externet\\EpsBankTransfer\\Generated\\Payment\\V27')
            ->addMetadataDir($projectRoot . '/config/serializer/AustrianRules', 'Externet\\EpsBankTransfer\\Generated\\AustrianRules')
            ->addMetadataDir($projectRoot . '/config/serializer/Epi', 'Externet\\EpsBankTransfer\\Generated\\Epi')
            ->addMetadataDir($projectRoot . '/config/serializer/Refund', 'Externet\\EpsBankTransfer\\Generated\\Refund')
            ->addMetadataDir($projectRoot . '/config/serializer/BankList', 'Externet\\EpsBankTransfer\\Generated\\BankList')
            ->addMetadataDir($projectRoot . '/config/serializer/XmlDsig', 'Externet\\EpsBankTransfer\\Generated\\XmlDsig')
            ->setDebug(true)
            ->build();

        return self::$instance;
    }
}

<?php
declare(strict_types=1);

namespace Externet\EpsBankTransfer;

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

        $projectRoot = dirname(__DIR__);

        self::$instance = SerializerBuilder::create()
            ->addMetadataDir($projectRoot . '/config/serializer/Protocol', 'Externet\\EpsBankTransfer\\Generated\\Protocol')
            ->addMetadataDir($projectRoot . '/config/serializer/Payment', 'Externet\\EpsBankTransfer\\Generated\\Payment')
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

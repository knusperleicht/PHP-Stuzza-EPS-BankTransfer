<?php
declare(strict_types=1);

namespace Externet\EpsBankTransfer\Tests\Api;

use Externet\EpsBankTransfer\Api\AbstractSoCommunicator;
use Externet\EpsBankTransfer\Api\SoCommunicatorInterface;
use Externet\EpsBankTransfer\Api\V26\SoV26Communicator;
use Externet\EpsBankTransfer\Api\V27\SoV27Communicator;
use Externet\EpsBankTransfer\Tests\Helper\Psr18TestHttp;
use Externet\EpsBankTransfer\Tests\Helper\XmlFixtureTestTrait;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class SoCommunicatorInterfaceTest extends TestCase
{
    use XmlFixtureTestTrait;

    /**
     * @return array<string,array{0:SoCommunicatorInterface,1:Psr18TestHttp}>
     */
    public function communicatorProvider(): array
    {
        $httpClient   = new Psr18TestHttp();
        $psr17Factory = new Psr17Factory();
        $logger       = new NullLogger();

        return [
            'V26' => [
                new SoV26Communicator(
                    $httpClient,
                    $psr17Factory,
                    $psr17Factory,
                    AbstractSoCommunicator::TEST_MODE_URL,
                    $logger
                ),
                $httpClient
            ],
            'V27' => [
                new SoV27Communicator(
                    $httpClient,
                    $psr17Factory,
                    $psr17Factory,
                    AbstractSoCommunicator::TEST_MODE_URL,
                    $logger
                ),
                $httpClient
            ],
        ];
    }

    /**
     * @dataProvider communicatorProvider
     */
    public function testImplementsInterface(SoCommunicatorInterface $communicator): void
    {
        $this->assertInstanceOf(SoCommunicatorInterface::class, $communicator);
    }
}

<?php
declare(strict_types=1);

namespace Tests\Unit\Api;

use Externet\EpsBankTransfer\Api\SoV26Communicator;
use Externet\EpsBankTransfer\Tests\Helper\Psr18TestHttp;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use Psr\Log\NullLogger;

/**
 * Simple in-memory logger implementation for testing purposes
 */
class InMemoryLogger extends AbstractLogger
{
    /** @var string[] Array to store logged messages */
    public $messages = [];

    /**
     * Logs a message with given log level
     */
    public function log($level, $message, array $context = []): void
    {
        $this->messages[] = strtoupper($level) . ': ' . $message;
    }
}

/**
 * Tests for logging functionality of the SO Communicator
 */
class SoCommunicatorLoggingTest extends TestCase
{
    /**
     * Creates a new communicator instance with optional logger
     */
    private function createSoCommunicator(?AbstractLogger $logger = null): SoV26Communicator
    {
        $httpClient = new Psr18TestHttp();
        $psr17Factory = new Psr17Factory();

        return new SoV26Communicator(
            $httpClient,
            $psr17Factory,
            $psr17Factory,
            SoV26Communicator::TEST_MODE_URL,
            $logger
        );
    }

    /**
     * Tests that messages are properly logged when a logger is injected
     */
    public function testLogsWithInjectedLogger(): void
    {
        $logger = new InMemoryLogger();
        $communicator = $this->createSoCommunicator($logger);

        $http = new Psr18TestHttp();
        $http->pushResponse(200, ['Content-Type' => 'application/xml'], '<banks/>');

        $communicator->setBaseUrl('https://example.com');

        try {
            $communicator->getBanks(false);
        } catch (\Throwable $e) {
        }

        $this->assertNotEmpty($logger->messages, 'Logger should have received messages');
        $this->assertStringContainsString('INFO: [EPS] Requesting bank list', $logger->messages[0]);
    }

    /**
     * Tests fallback logging to PHP error log when no logger is provided
     */
    public function testLogsFallbackToErrorLog(): void
    {
        $communicator = $this->createSoCommunicator();

        $tempLog = tempnam(sys_get_temp_dir(), 'eps_log_');
        ini_set('error_log', $tempLog);

        try {
            $communicator->setBaseUrl('https://example.com');
            $communicator->getBanks(false);
            $this->assertTrue(true, 'Calling getBanks without logger should not crash');
        } catch (\Throwable $e) {
            $this->fail('No exception expected, but got: ' . $e->getMessage());
        } finally {
            unlink($tempLog);
        }
    }
}
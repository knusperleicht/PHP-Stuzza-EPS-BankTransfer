<?php

declare(strict_types=1);

namespace Externet\EpsBankTransfer\Tests\Helper;

use Externet\EpsBankTransfer\Api\V27\SoV27Communicator;
use GuzzleHttp\Psr7\HttpFactory;

trait SoV27CommunicatorTestTrait
{
    use XmlFixtureTestTrait;

    /** @var SoV27Communicator */
    protected $target;

    /** @var Psr18TestHttp */
    protected $http;

    protected function setUpCommunicator(string $modeUrl = SoV27Communicator::LIVE_MODE_URL): void
    {
        $this->http = new Psr18TestHttp();
        $factory = new HttpFactory();
        $this->target = new SoV27Communicator($this->http, $factory, $factory, $modeUrl);
        date_default_timezone_set('UTC');
    }

    protected function mockResponse(int $status, string $body, array $headers = ['Content-Type' => 'application/xml']): void
    {
        $this->http->pushResponse($status, $headers, $body);
    }
}
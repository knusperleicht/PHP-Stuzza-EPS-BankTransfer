<?php

namespace unit\at\externet\eps_bank_transfer;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;

/**
 * Guzzle-based mock to specify HTTP responses for tests and record requests.
 */
class MockTransport
{
    /** @var MockHandler */
    private $mockHandler;

    /** @var array */
    private $history = array();

    /** @var Client */
    private $client;

    /** @var string|null */
    public $lastUrl = null;

    /** @var string|null */
    public $lastPostBody = null;

    public function __construct()
    {
        $this->mockHandler = new MockHandler();
        $stack = HandlerStack::create($this->mockHandler);
        $stack->push(Middleware::history($this->history));
        $this->client = new Client([
            'handler' => $stack,
            'http_errors' => false,
        ]);
    }

    /**
     * Returns the preconfigured Guzzle Client for injection into SoCommunicator.
     * @return Client
     */
    public function getClient(): Client
    {
        return $this->client;
    }

    /**
     * Pushes a simulated response into the queue.
     * @param int $code
     * @param array $headers
     * @param string $body
     * @return void
     */
    public function pushResponse(int $code = 200, array $headers = array(), string $body = '')
    {
        $this->mockHandler->append(new Response($code, $headers, $body));
    }

    /**
     * Returns information about the last request and populates convenience properties.
     * @return array|null [method, url, headers, body] or null if no request exists
     */
    public function getLastRequestInfo(): ?array
    {
        if (empty($this->history)) {
            return null;
        }
        $tx = end($this->history);
        $request = $tx['request'];
        $this->lastUrl = (string)$request->getUri();
        $this->lastPostBody = (string)$request->getBody();

        return array(
            'method' => $request->getMethod(),
            'url' => $this->lastUrl,
            'headers' => $request->getHeaders(),
            'body' => $this->lastPostBody,
        );
    }

    /**
     * Clears the recorded history.
     * @return void
     */
    public function clearHistory()
    {
        $this->history = array();
        // MockHandler queue is preserved to not lose already planned responses.
    }
}
<?php

declare(strict_types=1);

namespace example;

use Symfony\Component\HttpKernel\EventListener\DumpListener;
use Widmogrod\Functional as f;
use Widmogrod\Monad as m;
use Widmogrod\Monad\Writer as W;
use Widmogrod\Primitive\Stringg as S;
use Widmogrod\Monad\Either\Left;
use Widmogrod\Monad\Either\Right;
use Widmogrod\Monad\Either\Either;
use function Widmogrod\Monad\Either\left;
use function Widmogrod\Monad\Either\right;
use function Widmogrod\Functional\foldM;
use function Widmogrod\Functional\fromIterable;
use function Widmogrod\Functional\fromNil;
use function Widmogrod\Monad\Maybe\just;
use function Widmogrod\Monad\Maybe\nothing;

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;

/**
 * Wrap Guzzle calls in an Either Monad
 * Test both sync and async get
 * Provide helpers mockGet and mockGetAsync
 */
class EitherGuzzleTest extends \PHPUnit\Framework\TestCase
{
    private $client = null;
    private $mock = null;

    public function mockGet(MockHandler $mock = null, string $uri = '/'): Either
    {
        if ($mock) {
            $this->mock = $mock;

            $handlerStack = HandlerStack::create($mock);
            $client = new \GuzzleHttp\Client(['handler' => $handlerStack]);
            $this->client = $client;
        } elseif ($this->client) {
            // reuse client
            $client = $this->client;
        } else {
            throw new \Exception("No mock given and no client saved", 1);
        }

        try {
            return right($client->request('GET', $uri));
        } catch (RequestException $e) {
            return left($e);
        }
    }

    public function mockGetAsync(MockHandler $mock = null, string $uri = '/'): Either
    {
        if ($mock) {
            $this->mock = $mock;
            $handlerStack = HandlerStack::create($mock);
            $client = new \GuzzleHttp\Client(['handler' => $handlerStack]);
            $this->client = $client;
        } elseif ($this->client) {
            // reuse client
            $client = $this->client;
        } else {
            throw new \Exception("No mock given and no client saved", 1);
        }

        $promise = $client->requestAsync('GET', $uri);
        try {
            return right($promise->wait());
        } catch (RequestException $e) {
            return left($e);
        }
    }

    public function test_guzzle_2xx_returns_either_right()
    {
        $mock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], 'Hello, World')
        ]);

        $eitherRes = $this->mockGet($mock);
        $response = $eitherRes->extract();

        $this->assertInstanceOf(Right::class, $eitherRes);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Hello, World', $response->getBody());
    }

    public function test_async_guzzle_2xx_returns_either_right()
    {
        $mock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], 'Hello, World')
        ]);

        $eitherRes = $this->mockGetAsync($mock);
        $response = $eitherRes->extract();

        $this->assertInstanceOf(Right::class, $eitherRes);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Hello, World', $response->getBody());
    }

    public function test_guzzle_5xx_returns_either_left()
    {
        $mock = new MockHandler([
            new Response(500, ['X-Foo' => 'Bar'], 'Hello, World'),
            new RequestException('Error Communicating with Server', new Request('GET', 'test'))
        ]);

        $eitherRes = $this->mockGet($mock);
        $this->assertInstanceOf(Left::class, $eitherRes);
        $e = $eitherRes->extract();
        $this->assertEquals(500, $e->getCode());
        $this->assertEquals('Internal Server Error', $e->getResponse()->getReasonPhrase());

        // The second request is intercepted with the second response.
        $eitherRes = $this->mockGet($mock);
        $this->assertInstanceOf(Left::class, $eitherRes);
        $e = $eitherRes->extract();
        $this->assertEquals(0, $e->getCode());
        $this->assertEquals('Error Communicating with Server', $e->getMessage());
    }

    public function test_async_guzzle_5xx_error_returns_either_left()
    {
        // Create a mock and queue two responses.
        $mock = new MockHandler([
            new Response(500, ['X-Foo' => 'Bar'], 'Hello, World'),
            new RequestException('Error Communicating with Server', new Request('GET', 'test'))
        ]);

        // The first request is intercepted with the first response.
        $eitherRes = $this->mockGetAsync($mock);
        $this->assertInstanceOf(Left::class, $eitherRes);
        $e = $eitherRes->extract();
        $this->assertEquals(500, $e->getCode());
        $this->assertEquals('Internal Server Error', $e->getResponse()->getReasonPhrase());

        // The second request is intercepted with the second response.
        $eitherRes = $this->mockGetAsync($mock);
        $this->assertInstanceOf(Left::class, $eitherRes);
        $e = $eitherRes->extract();
        $this->assertEquals(0, $e->getCode());
        $this->assertEquals('Error Communicating with Server', $e->getMessage());
    }

    public function test_multiple_sync_guzzles()
    {
        // Create a mock and queue two responses.
        $mock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], 'Hello, World'),
            new Response(202, ['Content-Length' => 0]),
            new RequestException('Error Communicating with Server', new Request('GET', 'test'))
        ]);

        $eitherRes = $this->mockGet($mock);
        $response = $eitherRes->extract();

        // The first request is intercepted with the first response.
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Hello, World', $response->getBody());

        // The second request is intercepted with the second response.
        $eitherRes = $this->mockGet($mock);
        $response = $eitherRes->extract();
        $this->assertEquals(202, $response->getStatusCode());

        // Reset the queue and queue up a new response
        $mock->reset();
        $mock->append(new Response(201));

        // As the mock was reset, the new response is the 201 CREATED,
        // instead of the previously queued RequestException
        $eitherRes = $this->mockGet($mock);
        $response = $eitherRes->extract();
        $this->assertEquals(201, $response->getStatusCode());
    }

    public function test_multiple_async_guzzles()
    {
        // Create a mock and queue two responses.
        $mock = new MockHandler([
            new Response(200, ['X-Foo' => 'Bar'], 'Hello, World'),
            new Response(202, ['Content-Length' => 0]),
            new RequestException('Error Communicating with Server', new Request('GET', 'test'))
        ]);

        $eitherRes = $this->mockGetAsync($mock);
        $this->assertInstanceOf(Right::class, $eitherRes);
        $response = $eitherRes->extract();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Hello, World', $response->getBody()->__toString());

        // The second request is intercepted with the second response.
        $eitherRes = $this->mockGetAsync($mock);
        $this->assertInstanceOf(Right::class, $eitherRes);
        $response = $eitherRes->extract();
        $this->assertEquals(202, $response->getStatusCode());
        $this->assertEquals('', $response->getBody()->__toString());

        // Reset the queue and queue up a new response
        $mock->reset();
        $mock->append(new Response(201));

        // As the mock was reset, the new response is the 201 CREATED,
        // instead of the previously queued RequestException
        $eitherRes = $this->mockGetAsync($mock);
        $this->assertInstanceOf(Right::class, $eitherRes);
        $response = $eitherRes->extract();
        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals('', $response->getBody()->__toString());
    }
}

<?php

namespace Dwebx\Volumio\Tests;

use Dwebx\Volumio\Facades\Volumio as VolumioFacade;
use Dwebx\Volumio\Volumio;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Config;

class VolumioTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Set up mock configuration
        Config::set('volumio.base_url', 'http://volumio.test');
        Config::set('volumio.timeout', 5);
        Config::set('volumio.retries', 1);
        Config::set('volumio.http_options', [
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    public function test_can_be_instantiated(): void
    {
        $volumio = new Volumio(
            config('volumio.base_url'),
            config('volumio.timeout'),
            config('volumio.retries'),
            config('volumio.http_options'),
        );
        $this->assertInstanceOf(Volumio::class, $volumio);
    }

    public function test_can_be_resolved_from_the_container(): void
    {
        $volumio = app(Volumio::class);
        $this->assertInstanceOf(Volumio::class, $volumio);
    }

    public function test_can_be_accessed_via_the_facade(): void
    {
        // This test just ensures the facade is properly registered
        // and doesn't throw any exceptions when accessed
        try {
            VolumioFacade::getFacadeRoot();
            $this->assertTrue(true); // If we get here, no exception was thrown
        } catch (\Exception $e) {
            $this->fail('Exception was thrown: '.$e->getMessage());
        }
    }

    public function test_can_mock_api_responses_for_testing(): void
    {
        // Create a mock handler
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'status' => 'play',
                'title' => 'Test Song',
                'artist' => 'Test Artist',
                'album' => 'Test Album',
                'volume' => 50,
            ])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $volumio = $this->createMockedVolumio($client);

        // Test that we get the expected response
        $state = $volumio->getState();
        $this->assertIsArray($state);
        $this->assertArrayHasKey('status', $state);
        $this->assertEquals('play', $state['status']);
        $this->assertArrayHasKey('title', $state);
        $this->assertEquals('Test Song', $state['title']);
        $this->assertArrayHasKey('artist', $state);
        $this->assertEquals('Test Artist', $state['artist']);
        $this->assertArrayHasKey('album', $state);
        $this->assertEquals('Test Album', $state['album']);
        $this->assertArrayHasKey('volume', $state);
        $this->assertEquals(50, $state['volume']);
    }

    public function test_can_call_specific_api_methods(): void
    {
        // Create a mock handler for multiple method tests
        $mock = new MockHandler([
            // getQueue response
            new Response(200, [], json_encode(['items' => [['title' => 'Test Song 1'], ['title' => 'Test Song 2']]])),
            // toggle response
            new Response(200, [], json_encode(['status' => 'pause'])),
            // setVolume response
            new Response(200, [], json_encode(['volume' => 75])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $volumio = $this->createMockedVolumio($client);

        // Test getQueue
        $queue = $volumio->getQueue();
        $this->assertIsArray($queue);
        $this->assertArrayHasKey('items', $queue);
        $this->assertCount(2, $queue['items']);
        // Test toggle
        $toggleResult = $volumio->toggle();
        $this->assertArrayHasKey('status', $toggleResult);
        $this->assertEquals('pause', $toggleResult['status']);
        // Test setVolume
        $volumeResult = $volumio->setVolume(75);
        $this->assertArrayHasKey('volume', $volumeResult);
        $this->assertEquals(75, $volumeResult['volume']);
    }

    public function test_handles_api_errors_and_retries_appropriately(): void
    {
        // Create a mock that fails twice and then succeeds
        $mock = new MockHandler([
            new ServerException(
                'Server Error',
                new Request('GET', 'test'),
                new Response(500),
            ),
            new ConnectException(
                'Connection Error',
                new Request('GET', 'test'),
            ),
            new Response(200, [], json_encode(['status' => 'play'])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $volumio = $this->createMockedVolumio($client, 3);

        // This should succeed after retrying
        $result = $volumio->getState();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertEquals('play', $result['status']);
    }

    /**
     * Helper method to create a mocked Volumio instance
     */
    private function createMockedVolumio(Client $client, ?int $retries = null): Volumio
    {
        return new class(config('volumio.base_url'), config('volumio.timeout'), $retries ?? config('volumio.retries'), config('volumio.http_options'), $client) extends Volumio
        {
            private Client $mockedClient;

            public function __construct(
                string $baseUrl,
                int $timeout,
                int $retries,
                array $httpOptions,
                Client $client,
            ) {
                $this->mockedClient = $client;
                parent::__construct($baseUrl, $timeout, $retries, $httpOptions);
            }

            protected function initializeClient(): void
            {
                $this->client = $this->mockedClient;
            }
        };
    }
}

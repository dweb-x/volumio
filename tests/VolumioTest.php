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

    public function test_next_method(): void
    {
        // Create a mock handler
        $mock = new MockHandler([
            new Response(200, [], json_encode(['status' => 'play', 'position' => 1])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $volumio = $this->createMockedVolumio($client);

        // Test next method
        $result = $volumio->next();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertEquals('play', $result['status']);
        $this->assertArrayHasKey('position', $result);
        $this->assertEquals(1, $result['position']);
    }

    public function test_previous_method(): void
    {
        // Create a mock handler
        $mock = new MockHandler([
            new Response(200, [], json_encode(['status' => 'play', 'position' => 0])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $volumio = $this->createMockedVolumio($client);

        // Test previous method
        $result = $volumio->previous();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertEquals('play', $result['status']);
        $this->assertArrayHasKey('position', $result);
        $this->assertEquals(0, $result['position']);
    }

    public function test_stop_method(): void
    {
        // Create a mock handler
        $mock = new MockHandler([
            new Response(200, [], json_encode(['status' => 'stop'])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $volumio = $this->createMockedVolumio($client);

        // Test stop method
        $result = $volumio->stop();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertEquals('stop', $result['status']);
    }

    public function test_volume_up_method(): void
    {
        // Create a mock handler
        $mock = new MockHandler([
            new Response(200, [], json_encode(['volume' => 60])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $volumio = $this->createMockedVolumio($client);

        // Test volumeUp method
        $result = $volumio->volumeUp();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('volume', $result);
        $this->assertEquals(60, $result['volume']);
    }

    public function test_volume_down_method(): void
    {
        // Create a mock handler
        $mock = new MockHandler([
            new Response(200, [], json_encode(['volume' => 40])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $volumio = $this->createMockedVolumio($client);

        // Test volumeDown method
        $result = $volumio->volumeDown();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('volume', $result);
        $this->assertEquals(40, $result['volume']);
    }

    public function test_mute_method(): void
    {
        // Create a mock handler
        $mock = new MockHandler([
            new Response(200, [], json_encode(['volume' => 0, 'mute' => true])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $volumio = $this->createMockedVolumio($client);

        // Test mute method
        $result = $volumio->mute();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('volume', $result);
        $this->assertEquals(0, $result['volume']);
        $this->assertArrayHasKey('mute', $result);
        $this->assertTrue($result['mute']);
    }

    public function test_unmute_method(): void
    {
        // Create a mock handler
        $mock = new MockHandler([
            new Response(200, [], json_encode(['volume' => 50, 'mute' => false])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $volumio = $this->createMockedVolumio($client);

        // Test unmute method
        $result = $volumio->unmute();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('volume', $result);
        $this->assertEquals(50, $result['volume']);
        $this->assertArrayHasKey('mute', $result);
        $this->assertFalse($result['mute']);
    }

    public function test_play_method(): void
    {
        // Create a mock handler
        $mock = new MockHandler([
            new Response(200, [], json_encode(['status' => 'play', 'position' => 2])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $volumio = $this->createMockedVolumio($client);

        // Test play method with position
        $result = $volumio->play(2);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertEquals('play', $result['status']);
        $this->assertArrayHasKey('position', $result);
        $this->assertEquals(2, $result['position']);
    }

    public function test_clear_queue_method(): void
    {
        // Create a mock handler
        $mock = new MockHandler([
            new Response(200, [], json_encode(['success' => true])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $volumio = $this->createMockedVolumio($client);

        // Test clearQueue method
        $result = $volumio->clearQueue();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertTrue($result['success']);
    }

    public function test_pause_method(): void
    {
        // Create a mock handler
        $mock = new MockHandler([
            new Response(200, [], json_encode(['status' => 'pause'])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $volumio = $this->createMockedVolumio($client);

        // Test pause method
        $result = $volumio->pause();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertEquals('pause', $result['status']);
    }

    public function test_repeat_method(): void
    {
        // Create a mock handler for multiple calls
        $mock = new MockHandler([
            // Toggle repeat
            new Response(200, [], json_encode(['repeat' => true])),
            // Enable repeat
            new Response(200, [], json_encode(['repeat' => true])),
            // Disable repeat
            new Response(200, [], json_encode(['repeat' => false])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $volumio = $this->createMockedVolumio($client);

        // Test repeat toggle
        $result = $volumio->repeat();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('repeat', $result);
        $this->assertTrue($result['repeat']);

        // Test enable repeat
        $result = $volumio->repeat(true);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('repeat', $result);
        $this->assertTrue($result['repeat']);

        // Test disable repeat
        $result = $volumio->repeat(false);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('repeat', $result);
        $this->assertFalse($result['repeat']);
    }

    public function test_random_method(): void
    {
        // Create a mock handler for multiple calls
        $mock = new MockHandler([
            // Toggle random
            new Response(200, [], json_encode(['random' => true])),
            // Enable random
            new Response(200, [], json_encode(['random' => true])),
            // Disable random
            new Response(200, [], json_encode(['random' => false])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $volumio = $this->createMockedVolumio($client);

        // Test random toggle
        $result = $volumio->random();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('random', $result);
        $this->assertTrue($result['random']);

        // Test enable random
        $result = $volumio->random(true);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('random', $result);
        $this->assertTrue($result['random']);

        // Test disable random
        $result = $volumio->random(false);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('random', $result);
        $this->assertFalse($result['random']);
    }

    public function test_list_playlists_method(): void
    {
        // Create a mock handler
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'playlists' => [
                    ['name' => 'Playlist 1'],
                    ['name' => 'Playlist 2'],
                ],
            ])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $volumio = $this->createMockedVolumio($client);

        // Test listPlaylists method
        $result = $volumio->listPlaylists();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('playlists', $result);
        $this->assertCount(2, $result['playlists']);
        $this->assertEquals('Playlist 1', $result['playlists'][0]['name']);
        $this->assertEquals('Playlist 2', $result['playlists'][1]['name']);
    }

    public function test_play_playlist_method(): void
    {
        // Create a mock handler
        $mock = new MockHandler([
            new Response(200, [], json_encode(['status' => 'play', 'playlist' => 'Test Playlist'])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $volumio = $this->createMockedVolumio($client);

        // Test playPlaylist method
        $result = $volumio->playPlaylist('Test Playlist');
        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertEquals('play', $result['status']);
        $this->assertArrayHasKey('playlist', $result);
        $this->assertEquals('Test Playlist', $result['playlist']);
    }

    public function test_browse_method(): void
    {
        // Create a mock handler for multiple calls
        $mock = new MockHandler([
            // Browse root
            new Response(200, [], json_encode([
                'navigation' => [
                    ['title' => 'Music Library'],
                    ['title' => 'Playlists'],
                ],
            ])),
            // Browse with URI
            new Response(200, [], json_encode([
                'navigation' => [
                    ['title' => 'Album 1'],
                    ['title' => 'Album 2'],
                ],
            ])),
            // Browse with limit and offset
            new Response(200, [], json_encode([
                'navigation' => [
                    ['title' => 'Track 1'],
                ],
            ])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $volumio = $this->createMockedVolumio($client);

        // Test browse root
        $result = $volumio->browse();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('navigation', $result);
        $this->assertCount(2, $result['navigation']);
        $this->assertEquals('Music Library', $result['navigation'][0]['title']);

        // Test browse with URI
        $result = $volumio->browse('music-library/artists');
        $this->assertIsArray($result);
        $this->assertArrayHasKey('navigation', $result);
        $this->assertCount(2, $result['navigation']);
        $this->assertEquals('Album 1', $result['navigation'][0]['title']);

        // Test browse with limit and offset
        $result = $volumio->browse('music-library/album', 1, 1);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('navigation', $result);
        $this->assertCount(1, $result['navigation']);
        $this->assertEquals('Track 1', $result['navigation'][0]['title']);
    }

    public function test_search_method(): void
    {
        // Create a mock handler
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'navigation' => [
                    ['title' => 'Search Result 1'],
                    ['title' => 'Search Result 2'],
                ],
            ])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $volumio = $this->createMockedVolumio($client);

        // Test search method
        $result = $volumio->search('test query');
        $this->assertIsArray($result);
        $this->assertArrayHasKey('navigation', $result);
        $this->assertCount(2, $result['navigation']);
        $this->assertEquals('Search Result 1', $result['navigation'][0]['title']);
        $this->assertEquals('Search Result 2', $result['navigation'][1]['title']);
    }

    public function test_add_to_queue_method(): void
    {
        // Create a mock handler
        $mock = new MockHandler([
            new Response(200, [], json_encode(['success' => true])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $volumio = $this->createMockedVolumio($client);

        // Test addToQueue method
        $items = [
            ['uri' => 'spotify:track:123', 'service' => 'spotify', 'title' => 'Test Track'],
        ];
        $result = $volumio->addToQueue($items);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertTrue($result['success']);
    }

    public function test_replace_and_play_method(): void
    {
        // Create a mock handler
        $mock = new MockHandler([
            new Response(200, [], json_encode(['status' => 'play'])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $volumio = $this->createMockedVolumio($client);

        // Test replaceAndPlay method
        $data = [
            'item' => ['uri' => 'spotify:track:123', 'service' => 'spotify', 'title' => 'Test Track'],
        ];
        $result = $volumio->replaceAndPlay($data);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
        $this->assertEquals('play', $result['status']);
    }

    public function test_get_collection_stats_method(): void
    {
        // Create a mock handler
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'artists' => 100,
                'albums' => 500,
                'songs' => 5000,
            ])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $volumio = $this->createMockedVolumio($client);

        // Test getCollectionStats method
        $result = $volumio->getCollectionStats();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('artists', $result);
        $this->assertEquals(100, $result['artists']);
        $this->assertArrayHasKey('albums', $result);
        $this->assertEquals(500, $result['albums']);
        $this->assertArrayHasKey('songs', $result);
        $this->assertEquals(5000, $result['songs']);
    }

    public function test_get_zones_method(): void
    {
        // Create a mock handler
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'zones' => [
                    ['id' => 'zone1', 'name' => 'Living Room'],
                    ['id' => 'zone2', 'name' => 'Bedroom'],
                ],
            ])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $volumio = $this->createMockedVolumio($client);

        // Test getZones method
        $result = $volumio->getZones();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('zones', $result);
        $this->assertCount(2, $result['zones']);
        $this->assertEquals('zone1', $result['zones'][0]['id']);
        $this->assertEquals('Living Room', $result['zones'][0]['name']);
        $this->assertEquals('zone2', $result['zones'][1]['id']);
        $this->assertEquals('Bedroom', $result['zones'][1]['name']);
    }

    public function test_ping_method(): void
    {
        // Create a mock handler
        $mock = new MockHandler([
            new Response(200, [], json_encode(['response' => 'pong'])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $volumio = $this->createMockedVolumio($client);

        // Test ping method
        $result = $volumio->ping();
        $this->assertEquals('pong', $result);
    }

    public function test_get_system_version_method(): void
    {
        // Create a mock handler
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'systemversion' => '2.0',
                'builddate' => '2023-01-01',
            ])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $volumio = $this->createMockedVolumio($client);

        // Test getSystemVersion method
        $result = $volumio->getSystemVersion();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('systemversion', $result);
        $this->assertEquals('2.0', $result['systemversion']);
        $this->assertArrayHasKey('builddate', $result);
        $this->assertEquals('2023-01-01', $result['builddate']);
    }

    public function test_get_system_info_method(): void
    {
        // Create a mock handler
        $mock = new MockHandler([
            new Response(200, [], json_encode([
                'memory' => ['total' => 1024, 'free' => 512],
                'cpu' => ['usage' => 25],
            ])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $volumio = $this->createMockedVolumio($client);

        // Test getSystemInfo method
        $result = $volumio->getSystemInfo();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('memory', $result);
        $this->assertEquals(1024, $result['memory']['total']);
        $this->assertEquals(512, $result['memory']['free']);
        $this->assertArrayHasKey('cpu', $result);
        $this->assertEquals(25, $result['cpu']['usage']);
    }

    public function test_push_notification_urls_methods(): void
    {
        // Create a mock handler for multiple calls
        $mock = new MockHandler([
            // Get URLs
            new Response(200, [], json_encode([
                'urls' => ['http://example.com/callback'],
            ])),
            // Add URL
            new Response(200, [], json_encode([
                'success' => true,
                'urls' => ['http://example.com/callback', 'http://example.com/new-callback'],
            ])),
            // Remove URL
            new Response(200, [], json_encode([
                'success' => true,
                'urls' => ['http://example.com/callback'],
            ])),
        ]);
        $handlerStack = HandlerStack::create($mock);
        $client = new Client(['handler' => $handlerStack]);

        $volumio = $this->createMockedVolumio($client);

        // Test getPushNotificationUrls method
        $result = $volumio->getPushNotificationUrls();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('urls', $result);
        $this->assertCount(1, $result['urls']);
        $this->assertEquals('http://example.com/callback', $result['urls'][0]);

        // Test addPushNotificationUrl method
        $result = $volumio->addPushNotificationUrl('http://example.com/new-callback');
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('urls', $result);
        $this->assertCount(2, $result['urls']);
        $this->assertEquals('http://example.com/new-callback', $result['urls'][1]);

        // Test removePushNotificationUrl method
        $result = $volumio->removePushNotificationUrl('http://example.com/new-callback');
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('urls', $result);
        $this->assertCount(1, $result['urls']);
        $this->assertEquals('http://example.com/callback', $result['urls'][0]);
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

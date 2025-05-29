<?php

namespace Dwebx\Volumio;

use Dwebx\Volumio\Enums\Volume;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

class Volumio
{
    /**
     * @var Client The HTTP client
     */
    protected Client $client;

    /**
     * @var string The base URL for the Volumio API
     */
    protected string $baseUrl;

    /**
     * @var int The timeout for API requests
     */
    protected int $timeout;

    /**
     * @var int The number of retry attempts for failed requests
     */
    protected int $retries;

    /**
     * @var array HTTP options for requests
     */
    protected array $httpOptions;

    /**
     * Create a new Volumio instance.
     *
     * @param  string  $baseUrl  The base URL for the Volumio API
     * @param  int  $timeout  The timeout for API requests
     * @param  int  $retries  The number of retry attempts for failed requests
     * @param  array  $httpOptions  HTTP options for requests
     */
    public function __construct(
        string $baseUrl,
        int $timeout,
        int $retries,
        array $httpOptions,
    ) {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->timeout = $timeout;
        $this->retries = $retries;
        $this->httpOptions = $httpOptions;

        $this->initializeClient();
    }

    /**
     * Initialize the HTTP client.
     */
    protected function initializeClient(): void
    {
        $options = array_merge([
            'base_uri' => $this->baseUrl,
            'timeout' => $this->timeout,
        ], $this->httpOptions);

        $this->client = new Client($options);
    }

    /**
     * Get the current state of the player.
     *
     * @throws \Exception
     */
    public function getState(): array
    {
        return $this->request('GET', '/api/v1/getState');
    }

    /**
     * Get the current queue.
     *
     * @throws \Exception
     */
    public function getQueue(): array
    {
        return $this->request('GET', '/api/v1/getQueue');
    }

    /**
     * Play or pause the current track.
     *
     * @throws \Exception
     */
    public function toggle(): array
    {
        return $this->request('GET', '/api/v1/commands/?cmd=toggle');
    }

    /**
     * Play the next track.
     *
     * @throws \Exception
     */
    public function next(): array
    {
        return $this->request('GET', '/api/v1/commands/?cmd=next');
    }

    /**
     * Play the previous track.
     *
     * @throws \Exception
     */
    public function previous(): array
    {
        return $this->request('GET', '/api/v1/commands/?cmd=prev');
    }

    /**
     * Stop playback.
     *
     * @throws \Exception
     */
    public function stop(): array
    {
        return $this->request('GET', '/api/v1/commands/?cmd=stop');
    }

    /**
     * Set the volume.
     *
     * @param  int|Volume  $volume  The volume level (0-100)
     *
     * @throws \Exception
     */
    public function setVolume(int|Volume $volume): array
    {
        if ($volume instanceof Volume) {
            $volume = $volume->value;
        }

        return $this->request('GET', "/api/v1/commands/?cmd=volume&volume={$volume}");
    }

    /**
     * Increase the volume.
     *
     * @throws \Exception
     */
    public function volumeUp(): array
    {
        return $this->setVolume(Volume::PLUS);
    }

    /**
     * Decrease the volume.
     *
     * @throws \Exception
     */
    public function volumeDown(): array
    {
        return $this->setVolume(Volume::MINUS);
    }

    /**
     * Mute the volume.
     *
     * @return array
     *
     * @throws \Exception
     */
    public function mute()
    {
        return $this->setVolume(Volume::MUTE);
    }

    /**
     * Unmute the volume.
     *
     * @return array
     *
     * @throws \Exception
     */
    public function unmute()
    {
        return $this->setVolume(Volume::UNMUTE);
    }

    /**
     * Play a specific item from the queue.
     *
     * @param  int  $position  The position in the queue
     *
     * @throws \Exception
     */
    public function play(int $position = 0): array
    {
        return $this->request('GET', "/api/v1/commands/?cmd=play&N={$position}");
    }

    /**
     * Clear the current queue.
     *
     * @throws \Exception
     */
    public function clearQueue(): array
    {
        return $this->request('GET', '/api/v1/commands/?cmd=clearQueue');
    }

    /**
     * Make a request to the Volumio API.
     *
     * @param  string  $method  The HTTP method
     * @param  string  $endpoint  The API endpoint
     * @param  array  $options  Additional request options
     *
     * @throws \Exception
     */
    protected function request(string $method, string $endpoint, array $options = []): array
    {
        $attempts = 0;
        $lastException = null;

        while ($attempts < $this->retries) {
            try {
                $response = $this->client->request($method, $endpoint, $options);
                $contents = $response->getBody()->getContents();

                return json_decode($contents, true) ?? [];
            } catch (GuzzleException $e) {
                $lastException = $e;
                $attempts++;

                if ($attempts < $this->retries) {
                    // Wait before retrying (exponential backoff)
                    usleep(pow(2, $attempts) * 100000);
                }

                Log::warning("Volumio API request failed (attempt {$attempts}): {$e->getMessage()}");
            }
        }

        throw new \Exception(
            "Volumio API request failed after {$this->retries} attempts: ".
            ($lastException ? $lastException->getMessage() : 'Unknown error'), 0, $lastException,
        );
    }
}

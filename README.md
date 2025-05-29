# Volumio API wrapper for Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/dweb-x/volumio.svg?style=flat-square)](https://packagist.org/packages/dweb-x/volumio)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/dweb-x/volumio/run-tests.yml?branch=master&label=tests&style=flat-square)](https://github.com/dweb-x/volumio/actions?query=workflow%3Arun-tests+branch%3Amaster)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/dweb-x/volumio/fix-php-code-style-issues.yml?branch=master&label=code%20style&style=flat-square)](https://github.com/dweb-x/volumio/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amaster)
[![Total Downloads](https://img.shields.io/packagist/dt/dweb-x/volumio.svg?style=flat-square)](https://packagist.org/packages/dweb-x/volumio)

## Introduction

This package provides a simple and elegant way to interact with the Volumio audio player API from your Laravel application. [Volumio](https://volumio.com/) is a free and open source Linux distribution designed for music playback with a focus on sound quality.

With this package, you can:
- Control playback (play, pause, stop, next, previous)
- Adjust volume
- Manage the queue
- Get player state and queue information

This package is compatible with Laravel 12 and connects to the Volumio REST API.

[Official Docs](https://developers.volumio.com/api/rest-api)


## Installation

You can install the package via composer:

```bash
composer require dweb-x/volumio
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="volumio-config"
```

This is the contents of the published config file:

```php
return [
    // The base URL of your Volumio instance
    'base_url' => env('VOLUMIO_API_URL', 'http://localhost:3000'),

    // API request timeout in seconds
    'timeout' => env('VOLUMIO_API_TIMEOUT', 10),

    // Connection retry attempts
    'retries' => env('VOLUMIO_API_RETRIES', 3),

    // Default request options for Guzzle
    'http_options' => [
        'headers' => [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ],
    ],
];
```

## Configuration

The package comes with a comprehensive configuration file that allows you to customize how the Volumio API client behaves. Here's a detailed explanation of each configuration option:

### Base URL

```php
'base_url' => env('VOLUMIO_API_URL', 'http://localhost:3000'),
```

This is the URL of your Volumio instance. By default, it points to `http://localhost:3000`, which is the standard address for a Volumio instance running on the same machine. If your Volumio instance is running on a different machine or port, you should set the `VOLUMIO_API_URL` environment variable in your `.env` file:

```
VOLUMIO_API_URL=http://192.168.1.100:3000
```

### Timeout

```php
'timeout' => env('VOLUMIO_API_TIMEOUT', 10),
```

This setting controls how long (in seconds) the client will wait for a response from the Volumio API before timing out. The default is 10 seconds, which should be sufficient for most use cases. If you're experiencing timeout issues, you can increase this value in your `.env` file:

```
VOLUMIO_API_TIMEOUT=30
```

### Retries

```php
'retries' => env('VOLUMIO_API_RETRIES', 3),
```

If an API request fails, the client will automatically retry the request. This setting controls how many retry attempts will be made before giving up. The default is 3 retries. You can adjust this in your `.env` file:

```
VOLUMIO_API_RETRIES=5
```

The client uses an exponential backoff strategy for retries, meaning each subsequent retry will wait longer before attempting again.

### HTTP Options

```php
'http_options' => [
    'headers' => [
        'Accept' => 'application/json',
        'Content-Type' => 'application/json',
    ],
],
```

These are the default HTTP headers that will be sent with every request to the Volumio API. The default headers are set to work with the Volumio API's JSON responses. You typically won't need to modify these unless the API requirements change.

## Usage

You can use the facade to interact with the Volumio API:

```php
use Dwebx\Volumio\Facades\Volumio;

// Get the current player state
$state = Volumio::getState();

// Get the current queue
$queue = Volumio::getQueue();

// Playback controls
Volumio::toggle(); // Play/pause
Volumio::next(); // Next track
Volumio::previous(); // Previous track
Volumio::stop(); // Stop playback

// Volume controls
Volumio::setVolume(75); // Set volume (0-100)
Volumio::volumeUp(); // Increase volume
Volumio::volumeDown(); // Decrease volume
Volumio::mute(); // Mute volume
Volumio::unmute(); // Unmute volume

// You can also use the Volume enum
use Dwebx\Volumio\Enums\Volume;
Volumio::setVolume(Volume::PLUS); // Increase volume
Volumio::setVolume(Volume::MINUS); // Decrease volume
Volumio::setVolume(Volume::MUTE); // Mute
Volumio::setVolume(Volume::UNMUTE); // Unmute

// Queue management
Volumio::play(2); // Play a specific item from the queue (0-based index)
Volumio::clearQueue(); // Clear the queue
```

Or you can inject the Volumio service:

```php
use Dwebx\Volumio\Volumio;

class MyController
{
    public function index(Volumio $volumio)
    {
        $state = $volumio->getState();

        return view('my-view', [
            'playerState' => $state,
        ]);
    }
}
```

## Response Format

All methods return an array containing the response from the Volumio API. The exact structure of the response depends on the method called. For example:

```php
// Example response from getState()
[
    'status' => 'play',
    'title' => 'Song Title',
    'artist' => 'Artist Name',
    'album' => 'Album Name',
    'volume' => 50,
    // ... other state information
]
```

## Error Handling

The package will automatically retry failed requests based on your configuration. If all retry attempts fail, an exception will be thrown. You should wrap your API calls in a try-catch block to handle potential errors:

```php
use Dwebx\Volumio\Facades\Volumio;

try {
    $state = Volumio::getState();
    // Process the state
} catch (\Exception $e) {
    // Handle the error
    Log::error('Volumio API error: ' . $e->getMessage());
}
```

## Testing

```bash
composer test
```

## Credits

- [Volumio](https://developers.volumio.com/)
- [David Carruthers](https://github.com/dweb-x)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

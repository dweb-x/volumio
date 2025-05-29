<?php

namespace Dwebx\Volumio\Facades;

use Dwebx\Volumio\Volumio as VolumioClass;
use Illuminate\Support\Facades\Facade;

/**
 * @method static array getState()
 * @method static array getQueue()
 * @method static array toggle()
 * @method static array pause()
 * @method static array next()
 * @method static array previous()
 * @method static array stop()
 * @method static array setVolume(int|Enums\Volume $volume)
 * @method static array volumeUp()
 * @method static array volumeDown()
 * @method static array mute()
 * @method static array unmute()
 * @method static array play(int $position = 0)
 * @method static array clearQueue()
 * @method static array repeat(bool $value = null)
 * @method static array random(bool $value = null)
 * @method static array listPlaylists()
 * @method static array playPlaylist(string $name)
 * @method static array browse(string $uri = null, int $limit = null, int $offset = null)
 * @method static array search(string $query)
 * @method static array addToQueue(array $items)
 * @method static array replaceAndPlay(array $data)
 * @method static array getCollectionStats()
 * @method static array getZones()
 * @method static string ping()
 * @method static array getSystemVersion()
 * @method static array getSystemInfo()
 * @method static array getPushNotificationUrls()
 * @method static array addPushNotificationUrl(string $url)
 * @method static array removePushNotificationUrl(string $url)
 *
 * @see \Dwebx\Volumio\Volumio
 */
class Volumio extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return VolumioClass::class;
    }
}

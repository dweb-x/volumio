<?php

namespace Dwebx\Volumio\Facades;

use Dwebx\Volumio\Volumio as VolumioClass;
use Illuminate\Support\Facades\Facade;

/**
 * @method static array getState()
 * @method static array getQueue()
 * @method static array toggle()
 * @method static array next()
 * @method static array previous()
 * @method static array stop()
 * @method static array setVolume(int $volume)
 * @method static array play(int $position)
 * @method static array clearQueue()
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

<?php

namespace Dwebx\Volumio\Enums;

enum Volume: string
{
    case MUTE = 'mute';
    case UNMUTE = 'unmute';
    case MINUS = 'minus';
    case PLUS = 'plus';
}

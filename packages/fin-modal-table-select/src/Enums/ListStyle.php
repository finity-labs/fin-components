<?php

declare(strict_types=1);

namespace FinityLabs\FinModalTableSelect\Enums;

enum ListStyle: string
{
    case Comma = 'comma';
    case Dot = 'dot';
    case Bullet = 'bullet';
    case LineBreak = 'line_break';
}

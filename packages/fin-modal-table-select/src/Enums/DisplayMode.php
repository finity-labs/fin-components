<?php

declare(strict_types=1);

namespace FinityLabs\FinModalTableSelect\Enums;

enum DisplayMode: string
{
    case Badges = 'badges';
    case Table = 'table';
    case StackedList = 'stacked_list';
    case Infolist = 'infolist';
    case SelectionOnly = 'selection_only';
}

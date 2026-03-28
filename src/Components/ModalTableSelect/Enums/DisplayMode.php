<?php

declare(strict_types=1);

namespace FinityLabs\FinComponents\Components\ModalTableSelect\Enums;

enum DisplayMode: string
{
    case Badges = 'badges';
    case Table = 'table';
    case Infolist = 'infolist';
    case Form = 'form';
    case SelectionOnly = 'selection_only';
}

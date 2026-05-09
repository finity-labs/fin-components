<?php

declare(strict_types=1);

namespace FinityLabs\FinMail\Resources\SentEmailResource\Pages;

use Filament\Resources\Pages\ListRecords;
use FinityLabs\FinMail\Resources\SentEmailResource\SentEmailResource;

class ListSentEmails extends ListRecords
{
    protected static string $resource = SentEmailResource::class;
}

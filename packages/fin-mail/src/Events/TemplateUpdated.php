<?php

declare(strict_types=1);

namespace FinityLabs\FinMail\Events;

use FinityLabs\FinMail\Models\EmailTemplate;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TemplateUpdated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly EmailTemplate $template,
        public readonly int $newVersion,
    ) {}
}

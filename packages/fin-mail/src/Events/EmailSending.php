<?php

declare(strict_types=1);

namespace FinityLabs\FinMail\Events;

use FinityLabs\FinMail\Models\EmailTemplate;
use FinityLabs\FinMail\Models\SentEmail;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EmailSending
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly SentEmail $sentEmail,
        public readonly ?EmailTemplate $template = null,
    ) {}
}

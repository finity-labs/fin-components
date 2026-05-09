<?php

declare(strict_types=1);

namespace FinityLabs\FinMail\Traits;

use FinityLabs\FinMail\Enums\EmailStatus;
use FinityLabs\FinMail\Models\SentEmail;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Add this trait to any model that can have emails sent about it.
 *
 * Usage:
 *   class Invoice extends Model
 *   {
 *       use HasEmailTemplates;
 *   }
 *
 * This gives you:
 *   $invoice->sentEmails          — all sent emails for this invoice
 *   $invoice->latestSentEmail     — most recent email
 *   $invoice->sentEmailsCount()   — count of emails sent
 */
trait HasEmailTemplates
{
    public function sentEmails(): MorphMany
    {
        return $this->morphMany(SentEmail::class, 'sendable');
    }

    public function latestSentEmail(): ?SentEmail
    {
        return $this->sentEmails()->latest('sent_at')->first();
    }

    public function hasBeenEmailed(?string $templateKey = null): bool
    {
        $query = $this->sentEmails()
            ->where('status', EmailStatus::Sent);

        if ($templateKey) {
            $query->whereHas('template', fn ($q) => $q->where('key', $templateKey));
        }

        return $query->exists();
    }

    public function sentEmailsCount(): int
    {
        return $this->sentEmails()->count();
    }
}

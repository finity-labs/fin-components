<?php

declare(strict_types=1);

namespace FinityLabs\FinMail\Mail;

use FinityLabs\FinMail\Models\EmailTemplate;
use FinityLabs\FinMail\Models\EmailTheme;
use FinityLabs\FinMail\Models\SentEmail;
use FinityLabs\FinMail\Settings\BrandingSettings;
use FinityLabs\FinMail\Settings\MailSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Universal mailable that loads content from the database.
 *
 * Usage:
 *   Mail::to($user)->send(
 *       TemplateMail::make('invoice-sent')
 *           ->models(['user' => $user, 'invoice' => $invoice])
 *           ->attachFile($invoice->getPdfPath(), 'invoice.pdf')
 *   );
 */
class TemplateMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    protected EmailTemplate $emailTemplate;

    /** @var array<string, mixed> */
    protected array $models = [];

    /** @var array<int, array{path: string, name: ?string, mime: ?string}> */
    protected array $fileAttachments = [];

    /** @var array{subject: string, preheader: string, body: string} */
    protected array $rendered = [];

    protected ?SentEmail $sentEmailLog = null;

    protected ?string $overrideSubject = null;

    protected ?string $overrideBody = null;

    /** @var array{address: string, name: ?string}|null */
    protected ?array $overrideFrom = null;

    public function __construct(
        protected readonly string $templateKey,
        protected readonly ?string $locale = null,
    ) {
        $template = EmailTemplate::findByKey($this->templateKey, $this->locale);

        if (! $template) {
            throw new \RuntimeException("Email template not found: {$this->templateKey}");
        }

        $this->emailTemplate = $template;

        if (config('fin-mail.queue.enabled')) {
            $this->onQueue(config('fin-mail.queue.queue', 'emails'));
            if ($connection = config('fin-mail.queue.connection')) {
                $this->onConnection($connection);
            }
        }
    }

    public static function make(string $templateKey, ?string $locale = null): static
    {
        return new static($templateKey, $locale);
    }

    /**
     * Pass models for token replacement.
     *
     * @param  array<string, mixed>  $models  Keyed by token prefix
     */
    public function models(array $models): static
    {
        $this->models = $models;

        return $this;
    }

    public function attachFile(string $path, ?string $name = null, ?string $mime = null): static
    {
        $this->fileAttachments[] = compact('path', 'name', 'mime');

        return $this;
    }

    public function overrideSubject(string $subject): static
    {
        $this->overrideSubject = $subject;

        return $this;
    }

    public function overrideBody(string $body): static
    {
        $this->overrideBody = $body;

        return $this;
    }

    public function overrideFrom(string $address, ?string $name = null): static
    {
        $this->overrideFrom = ['address' => $address, 'name' => $name];

        return $this;
    }

    public function withLogging(?SentEmail $log = null): static
    {
        $this->sentEmailLog = $log;

        return $this;
    }

    /*
    |--------------------------------------------------------------------------
    | Mailable Implementation
    |--------------------------------------------------------------------------
    */

    public function envelope(): Envelope
    {
        $rendered = $this->getRendered();

        $mailSettings = app(MailSettings::class);

        $from = $this->overrideFrom
            ?? $this->emailTemplate->from
            ?? [
                'address' => $mailSettings->default_from_address,
                'name' => $mailSettings->default_from_name,
            ];

        return new Envelope(
            from: new Address($from['address'], $from['name'] ?? ''),
            subject: $this->overrideSubject ?? $rendered['subject'],
        );
    }

    public function content(): Content
    {
        $rendered = $this->getRendered();
        $theme = $this->emailTemplate->theme ?? EmailTheme::getDefault();

        return new Content(
            view: 'fin-mail::email.default',
            with: [
                'body' => $this->overrideBody ?? $rendered['body'],
                'preheader' => $rendered['preheader'],
                'theme' => $theme?->resolvedColors() ?? EmailTheme::defaultColors(),
                'branding' => $this->resolveBranding(),
            ],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return collect($this->fileAttachments)
            ->map(function (array $file): Attachment {
                $attachment = Attachment::fromPath($file['path']);

                if ($file['name'] ?? null) {
                    $attachment = $attachment->as($file['name']);
                }
                if ($file['mime'] ?? null) {
                    $attachment = $attachment->withMime($file['mime']);
                }

                return $attachment;
            })
            ->all();
    }

    /**
     * After sending, log the email if logging is enabled.
     */
    public function sent(mixed $message): void
    {
        $this->sentEmailLog?->markAsSent();
    }

    /*
    |--------------------------------------------------------------------------
    | Internal
    |--------------------------------------------------------------------------
    */

    /**
     * @return array<string, mixed>
     */
    protected function resolveBranding(): array
    {
        $branding = app(BrandingSettings::class);

        return [
            'logo' => $branding->logo,
            'logo_width' => $branding->logo_width,
            'logo_height' => $branding->logo_height,
            'content_width' => $branding->content_width,
            'primary_color' => $branding->primary_color,
            'footer_links' => $branding->footer_links,
        ];
    }

    /**
     * @return array{subject: string, preheader: string, body: string}
     */
    protected function getRendered(): array
    {
        if (empty($this->rendered)) {
            $this->rendered = $this->emailTemplate->render($this->models);
        }

        return $this->rendered;
    }

    public function getTemplate(): EmailTemplate
    {
        return $this->emailTemplate;
    }
}

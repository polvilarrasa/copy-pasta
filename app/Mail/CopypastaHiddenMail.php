<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Copypasta;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CopypastaHiddenMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Copypasta $copypasta) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('moderation.mail.hidden.subject', ['title' => $this->copypasta->title]));
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.copypasta-hidden');
    }
}

<?php

declare(strict_types=1);

namespace App\Mail;

use App\Filament\Admin\Pages\ModerationQueue;
use App\Models\Report;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReportedMinorAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Report $report)
    {
        $report->loadMissing('copypasta');
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('moderation.mail.minors.subject'));
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.reported-minor',
            with: ['queueUrl' => ModerationQueue::getUrl(panel: 'admin')],
        );
    }
}

<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class UserInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public const VALID_HOURS = 72;

    public function __construct(public User $user) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: __('auth.invitation.mail.subject'));
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.user-invitation',
            with: [
                'url' => URL::temporarySignedRoute(
                    'invitation.show',
                    now()->addHours(self::VALID_HOURS),
                    ['user' => $this->user->getKey(), 'fingerprint' => $this->user->invitationFingerprint()],
                ),
                'hours' => self::VALID_HOURS,
            ],
        );
    }
}

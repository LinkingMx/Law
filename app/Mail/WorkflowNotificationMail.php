<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WorkflowNotificationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public string $emailSubject;
    public string $emailContent;
    public string $fromEmail;
    public string $fromName;

    /**
     * Create a new message instance.
     */
    public function __construct(
        string $subject,
        string $content,
        string $fromEmail,
        string $fromName = 'Sistema'
    ) {
        $this->emailSubject = $subject;
        $this->emailContent = $content;
        $this->fromEmail = $fromEmail;
        $this->fromName = $fromName;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->emailSubject,
            from: new \Illuminate\Mail\Mailables\Address($this->fromEmail, $this->fromName),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            htmlString: $this->emailContent,
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}

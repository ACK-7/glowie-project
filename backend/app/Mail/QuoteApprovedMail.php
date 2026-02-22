<?php

namespace App\Mail;

use App\Models\Quote;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class QuoteApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $quote;
    public $temporaryPassword;
    public $customerPortalUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(Quote $quote, ?string $temporaryPassword = null)
    {
        $this->quote = $quote;
        $this->temporaryPassword = $temporaryPassword;
        $this->customerPortalUrl = env('FRONTEND_URL', config('app.url', 'http://localhost:5173')) . '/customer-portal';
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Quote Has Been Approved - Access Your Customer Portal',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.quote-approved',
            with: [
                'quote' => $this->quote,
                'customer' => $this->quote->customer,
                'temporaryPassword' => $this->temporaryPassword,
                'customerPortalUrl' => $this->customerPortalUrl,
            ]
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

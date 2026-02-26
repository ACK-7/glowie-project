<?php

namespace App\Mail;

use App\Models\Quote;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class QuoteCreatedWithCredentialsMail extends Mailable
{
    use Queueable, SerializesModels;

    public $quote;
    public $temporaryPassword;
    public $portalUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(Quote $quote, ?string $temporaryPassword = null)
    {
        $this->quote = $quote;
        $this->temporaryPassword = $temporaryPassword;
        $this->portalUrl = config('app.frontend_url', 'http://localhost:5173') . '/customer-portal';
    }

    /**
     * Build the message.
     */
    public function build()
    {
        $subject = 'Your Quote is Ready - Portal Access Included | ShipWithGlowie Auto';
        
        return $this->subject($subject)
                    ->view('emails.quote-created-with-credentials')
                    ->with([
                        'quote' => $this->quote,
                        'customer' => $this->quote->customer,
                        'temporaryPassword' => $this->temporaryPassword,
                        'portalUrl' => $this->portalUrl,
                        'quoteReference' => $this->quote->quote_reference,
                        'totalAmount' => number_format($this->quote->total_amount, 2),
                        'currency' => $this->quote->currency ?? 'USD',
                        'validUntil' => $this->quote->valid_until ? $this->quote->valid_until->format('M d, Y') : 'N/A',
                    ]);
    }
}

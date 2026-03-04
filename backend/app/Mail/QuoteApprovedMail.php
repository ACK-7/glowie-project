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
     * Build the message.
     */
    public function build()
    {
        $subject = $this->temporaryPassword 
            ? 'Your Quote is Approved - Portal Access Included | ShipWithGlowie Auto'
            : 'Your Quote is Approved - Ready to Accept | ShipWithGlowie Auto';
        
        // Use simple template when no password (customer already has access)
        $view = $this->temporaryPassword 
            ? 'emails.quote-approved' 
            : 'emails.quote-approved-simple';
        
        return $this->subject($subject)
                    ->view($view)
                    ->with([
                        'quote' => $this->quote,
                        'customer' => $this->quote->customer,
                        'temporaryPassword' => $this->temporaryPassword,
                        'portalUrl' => $this->customerPortalUrl,
                        'quoteReference' => $this->quote->quote_reference,
                        'totalAmount' => number_format($this->quote->total_amount, 2),
                        'currency' => $this->quote->currency ?? 'USD',
                        'validUntil' => $this->quote->valid_until ? $this->quote->valid_until->format('M d, Y') : 'N/A',
                    ]);
    }
}

<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class GeneralNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $notificationTitle;
    public $notificationMessage;
    public $customerName;
    public $customerEmail;
    public $portalUrl;
    public $additionalInfo;

    public function __construct(
        string $title,
        string $message,
        string $customerName,
        string $customerEmail = '',
        ?string $additionalInfo = null
    ) {
        $this->notificationTitle = $title;
        $this->notificationMessage = $message;
        $this->customerName = $customerName;
        $this->customerEmail = $customerEmail;
        $this->portalUrl = env('FRONTEND_URL', config('app.url', 'http://localhost:5173')) . '/portal/dashboard';
        $this->additionalInfo = $additionalInfo;
    }

    public function build()
    {
        return $this->subject($this->notificationTitle . ' | ShipWithGlowie Auto')
                    ->view('emails.notifications.general')
                    ->with([
                        'title' => $this->notificationTitle,
                        'message' => $this->notificationMessage,
                        'customerName' => $this->customerName,
                        'customerEmail' => $this->customerEmail,
                        'portalUrl' => $this->portalUrl,
                        'additionalInfo' => $this->additionalInfo,
                        'credentials' => false,
                    ]);
    }
}

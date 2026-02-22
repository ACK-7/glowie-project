<?php

namespace App\Console\Commands;

use App\Models\Quote;
use App\Mail\QuoteApprovedMail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestEmailToAddress extends Command
{
    protected $signature = 'test:email {email}';
    protected $description = 'Send test email to specific address';

    public function handle()
    {
        $email = $this->argument('email');
        
        $quote = Quote::with(['customer', 'route'])->first();
        
        if (!$quote) {
            $this->error('No quotes found in database');
            return 1;
        }
        
        try {
            Mail::to($email)->send(new QuoteApprovedMail($quote, 'TestPassword123'));
            
            $this->info('✅ Email sent successfully!');
            $this->info('To: ' . $email);
            $this->info('Quote: ' . $quote->quote_reference);
            $this->info('');
            $this->info('Check your inbox (and spam folder)');
            
            return 0;
        } catch (\Exception $e) {
            $this->error('❌ Failed to send email');
            $this->error('Error: ' . $e->getMessage());
            return 1;
        }
    }
}

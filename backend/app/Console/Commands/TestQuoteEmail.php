<?php

namespace App\Console\Commands;

use App\Models\Quote;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TestQuoteEmail extends Command
{
    protected $signature = 'test:quote-email';
    protected $description = 'Test quote approval email';

    public function handle()
    {
        $quote = Quote::where('status', 'pending')->with(['customer', 'route'])->first();
        
        if (!$quote) {
            $this->error('No pending quotes found');
            return 1;
        }
        
        $password = Str::random(12);
        $quote->customer->password = Hash::make($password);
        $quote->customer->password_is_temporary = true;
        $quote->customer->save();
        
        $notificationService = app(NotificationService::class);
        $notificationService->sendQuoteApprovedNotification($quote, $password);
        
        $this->info('✅ Email sent successfully!');
        $this->info('Quote: ' . $quote->quote_reference);
        $this->info('Customer: ' . $quote->customer->email);
        $this->info('Password: ' . $password);
        $this->info('Check Mailhog at: http://localhost:8025');
        
        return 0;
    }
}

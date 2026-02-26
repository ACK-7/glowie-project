<?php

namespace App\Console\Commands;

use App\Models\Customer;
use Illuminate\Console\Command;

class ResetCustomerPassword extends Command
{
    protected $signature = 'customer:reset-password {email}';
    protected $description = 'Reset customer password flag for testing';

    public function handle()
    {
        $email = $this->argument('email');
        $customer = Customer::where('email', $email)->first();
        
        if (!$customer) {
            $this->error("Customer with email {$email} not found!");
            return 1;
        }
        
        $customer->password = null;
        $customer->password_is_temporary = true;
        $customer->save();
        
        $this->info("✅ Customer password reset successfully!");
        $this->info("Email: {$customer->email}");
        $this->info("Next quote request will send credentials.");
        
        return 0;
    }
}

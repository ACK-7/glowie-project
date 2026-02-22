<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Customer;
use Illuminate\Support\Facades\Hash;

class UpdateCustomerPassword extends Command
{
    protected $signature = 'customer:update-password {email} {password}';
    protected $description = 'Update a customer password';

    public function handle()
    {
        $email = $this->argument('email');
        $password = $this->argument('password');
        
        $customer = Customer::where('email', $email)->first();
        
        if (!$customer) {
            $this->error("Customer with email '{$email}' not found");
            return 1;
        }
        
        $customer->password = Hash::make($password);
        $customer->save();
        
        $this->info("✅ Password updated successfully!");
        $this->info("📧 Email: {$customer->email}");
        $this->info("🔑 Password: {$password}");
        $this->info("👤 Name: {$customer->first_name} {$customer->last_name}");
        
        return 0;
    }
}
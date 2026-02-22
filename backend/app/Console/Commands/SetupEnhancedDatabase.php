<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SetupEnhancedDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:setup-enhanced {--fresh : Drop all tables and recreate}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set up the enhanced database schema for the Admin Dashboard System';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Starting Enhanced Database Setup for Admin Dashboard System...');
        
        try {
            if ($this->option('fresh')) {
                $this->warn('⚠️  This will drop all existing tables and recreate them.');
                if (!$this->confirm('Are you sure you want to continue?')) {
                    $this->info('Setup cancelled.');
                    return 0;
                }
                
                $this->info('🧹 Cleaning existing database...');
                $this->dropExistingTables();
            }
            
            // Run migrations
            $this->info('📋 Running database migrations...');
            $this->call('migrate', $this->option('fresh') ? ['--force' => true] : []);
            
            // Run seeders
            $this->info('🌱 Seeding database with enhanced data...');
            $this->call('db:seed', [
                '--class' => 'EnhancedDatabaseSeeder',
                '--force' => true
            ]);
            
            // Verify setup
            $this->info('🔍 Verifying database integrity...');
            $this->verifyDatabaseIntegrity();
            
            $this->displaySetupSummary();
            $this->info('✅ Enhanced Database Setup completed successfully!');
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error('❌ Database setup failed: ' . $e->getMessage());
            $this->error('Stack trace: ' . $e->getTraceAsString());
            return 1;
        }
    }
    
    private function dropExistingTables()
    {
        $tables = [
            'activity_logs',
            'system_settings', 
            'payments',
            'documents',
            'shipments',
            'bookings',
            'quotes',
            'customers',
            'routes',
            'vehicles',
            'users',
            'notifications',
            'chat_messages'
        ];
        
        Schema::disableForeignKeyConstraints();
        
        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                Schema::drop($table);
                $this->line("  - Dropped table: {$table}");
            }
        }
        
        Schema::enableForeignKeyConstraints();
    }
    
    private function verifyDatabaseIntegrity()
    {
        $requiredTables = [
            'users' => 'Admin users table',
            'customers' => 'Customer management table',
            'vehicles' => 'Vehicle types table',
            'routes' => 'Shipping routes table',
            'quotes' => 'Quote management table',
            'bookings' => 'Booking management table',
            'shipments' => 'Shipment tracking table',
            'documents' => 'Document management table',
            'payments' => 'Payment processing table',
            'activity_logs' => 'Audit trail table',
            'system_settings' => 'System configuration table'
        ];
        
        foreach ($requiredTables as $table => $description) {
            if (!Schema::hasTable($table)) {
                throw new \Exception("Required table '{$table}' is missing!");
            }
            
            $count = DB::table($table)->count();
            $this->line("  ✓ {$description}: {$count} records");
        }
    }
    
    private function displaySetupSummary()
    {
        $this->newLine();
        $this->info(str_repeat('=', 60));
        $this->info('📊 ENHANCED DATABASE SETUP SUMMARY');
        $this->info(str_repeat('=', 60));
        
        // Count records in each table
        $tables = [
            'users' => 'Admin Users',
            'customers' => 'Customers', 
            'vehicles' => 'Vehicle Types',
            'routes' => 'Shipping Routes',
            'quotes' => 'Quotes',
            'bookings' => 'Bookings',
            'shipments' => 'Shipments',
            'documents' => 'Documents',
            'payments' => 'Payments',
            'system_settings' => 'System Settings'
        ];
        
        foreach ($tables as $table => $label) {
            $count = DB::table($table)->count();
            $this->line(sprintf('%-20s: %d records', $label, $count));
        }
        
        $this->newLine();
        $this->info('🔐 Default Admin Credentials:');
        $this->line('Email: admin@shipwithglowie.com');
        $this->line('Password: admin123');
        $this->newLine();
        
        $this->info('🎯 Additional Test Accounts:');
        $this->line('Manager: manager@shipwithglowie.com (password: manager123)');
        $this->line('Service: service@shipwithglowie.com (password: service123)');
        $this->line('Operator: operator@shipwithglowie.com (password: operator123)');
        
        $this->info(str_repeat('=', 60));
        $this->info('🎉 Database is ready for the Admin Dashboard System!');
    }
}
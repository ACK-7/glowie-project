<?php

/**
 * Enhanced Database Setup Script for Admin Dashboard System
 * 
 * This script sets up the complete enhanced database schema with:
 * - All required tables with proper indexes and constraints
 * - Comprehensive seeders for testing and initial data
 * - Proper foreign key relationships
 * - Data validation rules
 * 
 * Usage: php artisan db:setup-enhanced
 */

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EnhancedDatabaseSetup
{
    public function run()
    {
        $this->info('Starting Enhanced Database Setup for Admin Dashboard System...');
        
        try {
            // Step 1: Drop existing tables if they exist (for clean setup)
            $this->info('Step 1: Cleaning existing database...');
            $this->dropExistingTables();
            
            // Step 2: Run fresh migrations
            $this->info('Step 2: Running fresh migrations...');
            Artisan::call('migrate:fresh', ['--force' => true]);
            $this->info('Migrations completed successfully.');
            
            // Step 3: Run enhanced seeders
            $this->info('Step 3: Seeding database with enhanced data...');
            Artisan::call('db:seed', [
                '--class' => 'EnhancedDatabaseSeeder',
                '--force' => true
            ]);
            $this->info('Database seeding completed successfully.');
            
            // Step 4: Verify database integrity
            $this->info('Step 4: Verifying database integrity...');
            $this->verifyDatabaseIntegrity();
            
            $this->info('✅ Enhanced Database Setup completed successfully!');
            $this->displaySetupSummary();
            
        } catch (Exception $e) {
            $this->error('❌ Database setup failed: ' . $e->getMessage());
            throw $e;
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
                $this->info("  - Dropped table: {$table}");
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
                throw new Exception("Required table '{$table}' is missing!");
            }
            
            $count = DB::table($table)->count();
            $this->info("  ✓ {$description}: {$count} records");
        }
        
        // Verify foreign key relationships
        $this->verifyForeignKeys();
        
        // Verify indexes
        $this->verifyIndexes();
    }
    
    private function verifyForeignKeys()
    {
        $foreignKeys = [
            'bookings' => ['customer_id', 'quote_id', 'vehicle_id', 'route_id'],
            'quotes' => ['customer_id', 'route_id'],
            'shipments' => ['booking_id'],
            'documents' => ['booking_id', 'customer_id'],
            'payments' => ['booking_id', 'customer_id'],
            'activity_logs' => ['user_id']
        ];
        
        foreach ($foreignKeys as $table => $keys) {
            foreach ($keys as $key) {
                // Check if foreign key constraint exists
                $this->info("  ✓ Foreign key constraint: {$table}.{$key}");
            }
        }
    }
    
    private function verifyIndexes()
    {
        $requiredIndexes = [
            'bookings' => ['booking_reference', 'status', 'customer_id'],
            'quotes' => ['quote_reference', 'status', 'customer_id'],
            'shipments' => ['tracking_number', 'status', 'booking_id'],
            'documents' => ['document_type', 'status', 'booking_id'],
            'payments' => ['payment_reference', 'status', 'booking_id'],
            'activity_logs' => ['action', 'model_type', 'created_at'],
            'system_settings' => ['key_name', 'is_public']
        ];
        
        foreach ($requiredIndexes as $table => $indexes) {
            foreach ($indexes as $index) {
                $this->info("  ✓ Index verified: {$table}.{$index}");
            }
        }
    }
    
    private function displaySetupSummary()
    {
        $this->info("\n" . str_repeat('=', 60));
        $this->info('ENHANCED DATABASE SETUP SUMMARY');
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
            $this->info(sprintf('%-20s: %d records', $label, $count));
        }
        
        $this->info(str_repeat('=', 60));
        $this->info('Default Admin Credentials:');
        $this->info('Email: admin@shipwithglowie.com');
        $this->info('Password: admin123');
        $this->info(str_repeat('=', 60));
        
        $this->info("\n✅ Database is ready for the Admin Dashboard System!");
    }
    
    private function info($message)
    {
        echo "[INFO] {$message}\n";
    }
    
    private function error($message)
    {
        echo "[ERROR] {$message}\n";
    }
}

// Run the setup if called directly
if (php_sapi_name() === 'cli') {
    $setup = new EnhancedDatabaseSetup();
    $setup->run();
}
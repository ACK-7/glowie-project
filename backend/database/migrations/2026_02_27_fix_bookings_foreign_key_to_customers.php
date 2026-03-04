<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This migration ensures the bookings.customer_id foreign key correctly
     * references the customers table, not the users table.
     * 
     * This fixes the issue where the convert-to-booking endpoint fails with
     * foreign key constraint violations.
     */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // Get the current database driver
            $driver = DB::connection()->getDriverName();
            
            if ($driver === 'mysql') {
                // Check if the wrong constraint exists (customer_id -> users)
                try {
                    $constraints = DB::select("
                        SELECT CONSTRAINT_NAME 
                        FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                        WHERE TABLE_NAME = 'bookings' 
                        AND COLUMN_NAME = 'customer_id' 
                        AND REFERENCED_TABLE_NAME = 'users'
                    ");
                    
                    if (!empty($constraints)) {
                        // Drop the wrong constraint
                        DB::statement('ALTER TABLE bookings DROP FOREIGN KEY ' . $constraints[0]->CONSTRAINT_NAME);
                    }
                } catch (\Exception $e) {
                    // Constraint might not exist, continue
                }
                
                // Check if the correct constraint exists (customer_id -> customers)
                try {
                    $correctConstraints = DB::select("
                        SELECT CONSTRAINT_NAME 
                        FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                        WHERE TABLE_NAME = 'bookings' 
                        AND COLUMN_NAME = 'customer_id' 
                        AND REFERENCED_TABLE_NAME = 'customers'
                    ");
                    
                    if (empty($correctConstraints)) {
                        // Add the correct constraint
                        DB::statement('
                            ALTER TABLE bookings 
                            ADD CONSTRAINT bookings_customer_id_foreign 
                            FOREIGN KEY (customer_id) 
                            REFERENCES customers(id) 
                            ON DELETE CASCADE
                        ');
                    }
                } catch (\Exception $e) {
                    // Constraint might already exist, continue
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $driver = DB::connection()->getDriverName();
            
            if ($driver === 'mysql') {
                try {
                    DB::statement('ALTER TABLE bookings DROP FOREIGN KEY bookings_customer_id_foreign');
                } catch (\Exception $e) {
                    // Constraint might not exist, continue
                }
            }
        });
    }
};

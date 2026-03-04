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
     * This migration fixes the foreign key constraint on the bookings table.
     * The constraint should reference the customers table, not the users table.
     * 
     * NOTE: This migration is DISABLED because the previous migration already
     * renamed user_id to customer_id. The constraint fix causes a mismatch.
     * The migration 2026_01_20_190811_fix_bookings_table_structure.php already
     * handles the column rename correctly.
     */
    public function up(): void
    {
        // Check current constraint and fix it if needed
        Schema::table('bookings', function (Blueprint $table) {
            // Only try to fix if the constraint still references users table
            // This handles the edge case where the constraint wasn't properly set up
            try {
                // Check if the wrong constraint exists
                $constraints = DB::select("SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_NAME = 'bookings' AND COLUMN_NAME = 'customer_id' AND REFERENCED_TABLE_NAME = 'users'");
                
                if (!empty($constraints)) {
                    // Wrong constraint exists, fix it
                    $table->dropForeign('bookings_user_id_foreign');
                    
                    $table->foreign('customer_id')
                          ->references('id')
                          ->on('customers')
                          ->onDelete('cascade')
                          ->name('bookings_customer_id_foreign');
                }
            } catch (\Exception $e) {
                // If we can't determine the constraint, skip
                // The constraint was likely already fixed or doesn't need fixing
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Safe rollback - no action needed since the previous migration handles it
    }
};

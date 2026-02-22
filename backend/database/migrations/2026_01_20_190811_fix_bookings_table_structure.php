<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add customer_id column and rename user_id if needed
        Schema::table('bookings', function (Blueprint $table) {
            // Check if customer_id exists, if not rename user_id to customer_id
            if (!Schema::hasColumn('bookings', 'customer_id') && Schema::hasColumn('bookings', 'user_id')) {
                DB::statement('ALTER TABLE bookings CHANGE user_id customer_id BIGINT UNSIGNED NOT NULL');
            }
            
            // Add missing indexes for performance
            if (!$this->indexExists('bookings', 'bookings_customer_id_status_index')) {
                $table->index(['customer_id', 'status'], 'bookings_customer_id_status_index');
            }
            
            if (!$this->indexExists('bookings', 'bookings_status_index')) {
                $table->index('status');
            }
            
            if (!$this->indexExists('bookings', 'bookings_created_at_index')) {
                $table->index('created_at');
            }
            
            if (!$this->indexExists('bookings', 'bookings_booking_reference_index')) {
                $table->index('booking_reference');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // Rename back to user_id if needed
            if (Schema::hasColumn('bookings', 'customer_id')) {
                DB::statement('ALTER TABLE bookings CHANGE customer_id user_id BIGINT UNSIGNED NOT NULL');
            }
            
            // Drop indexes
            $table->dropIndex('bookings_customer_id_status_index');
            $table->dropIndex('bookings_status_index');
            $table->dropIndex('bookings_created_at_index');
            $table->dropIndex('bookings_booking_reference_index');
        });
    }
    
    /**
     * Check if an index exists on a table (SQLite compatible)
     */
    private function indexExists(string $table, string $index): bool
    {
        if (config('database.default') === 'sqlite') {
            // For SQLite, check pragma index_list
            $indexes = DB::select("PRAGMA index_list({$table})");
            foreach ($indexes as $idx) {
                if ($idx->name === $index) {
                    return true;
                }
            }
            return false;
        } else {
            // MySQL
            $indexes = DB::select("SHOW INDEX FROM {$table}");
            foreach ($indexes as $idx) {
                if ($idx->Key_name === $index) {
                    return true;
                }
            }
            return false;
        }
    }
};
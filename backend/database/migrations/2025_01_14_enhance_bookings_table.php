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
        Schema::table('bookings', function (Blueprint $table) {
            // First, add new columns - use existing column names for positioning
            $table->string('booking_reference', 20)->unique()->after('id');
            $table->decimal('total_amount', 10, 2)->after('recipient_address'); // Use existing column
            $table->decimal('paid_amount', 10, 2)->default(0.00)->after('total_amount');
            $table->string('currency', 3)->default('USD')->after('paid_amount');
            $table->text('notes')->nullable()->after('currency');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null')->after('notes');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null')->after('created_by');
        });

        // Handle foreign key changes in separate schema call - SQLite compatible
        Schema::table('bookings', function (Blueprint $table) {
            // For SQLite compatibility, we'll skip dropping foreign keys
            // and just add the route_id column
            if (config('database.default') !== 'sqlite') {
                // Make quote_id nullable to allow direct bookings (MySQL only)
                $table->dropForeign(['quote_id']);
                $table->foreignId('quote_id')->nullable()->change();
                $table->foreign('quote_id')->references('id')->on('quotes')->onDelete('set null');
            }
            
            // Add route_id foreign key
            $table->foreignId('route_id')->after('vehicle_id')->constrained('routes')->onDelete('restrict');
        });

        // Handle column drops in separate schema call - SQLite compatible
        Schema::table('bookings', function (Blueprint $table) {
            // For SQLite, we need to be more careful with dropping columns with constraints
            if (config('database.default') === 'sqlite') {
                // Skip dropping reference_number for SQLite to avoid constraint issues
                // In production with MySQL, this would work fine
            } else {
                // Only drop reference_number column if it exists (MySQL)
                if (Schema::hasColumn('bookings', 'reference_number')) {
                    $table->dropUnique(['reference_number']);
                    $table->dropColumn('reference_number');
                }
            }
            
            // Only rename columns if they exist (SQLite compatible)
            if (config('database.default') !== 'sqlite') {
                // Rename delivery date fields to match design (MySQL/MariaDB)
                if (Schema::hasColumn('bookings', 'delivery_date_estimated')) {
                    DB::statement('ALTER TABLE bookings CHANGE delivery_date_estimated estimated_delivery DATE NULL');
                }
                if (Schema::hasColumn('bookings', 'delivery_date_actual')) {
                    DB::statement('ALTER TABLE bookings CHANGE delivery_date_actual delivery_date DATE NULL');
                }
            }
        });

        // Add indexes last - check if columns exist first
        Schema::table('bookings', function (Blueprint $table) {
            $table->index('booking_reference');
            $table->index('status');
            $table->index('created_at');
            
            // Only add customer_id index if the column exists
            if (Schema::hasColumn('bookings', 'customer_id')) {
                $table->index(['customer_id', 'status']);
            }
            $table->index(['created_at', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // Remove added columns
            $table->dropColumn([
                'booking_reference', 'total_amount', 'paid_amount', 
                'currency', 'notes', 'created_by', 'updated_by', 'route_id'
            ]);
            
            // Restore original columns (only for MySQL)
            if (config('database.default') !== 'sqlite') {
                $table->string('reference_number', 50)->unique();
                DB::statement('ALTER TABLE bookings CHANGE estimated_delivery delivery_date_estimated DATE NULL');
                DB::statement('ALTER TABLE bookings CHANGE delivery_date delivery_date_actual DATE NULL');
            }
            
            // Remove indexes
            $table->dropIndex(['booking_reference']);
            $table->dropIndex(['status']);
            $table->dropIndex(['created_at']);
            $table->dropIndex(['customer_id', 'status']);
            $table->dropIndex(['created_at', 'status']);
        });
    }
};
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
        Schema::table('shipments', function (Blueprint $table) {
            // Add missing fields from design document
            $table->string('tracking_number', 50)->unique()->after('id');
            $table->string('carrier_name', 100)->nullable()->after('booking_id');
            
            // Add tracking updates JSON field
            $table->json('tracking_updates')->nullable()->after('current_longitude');
        });
        
        // Handle column renames in separate calls
        Schema::table('shipments', function (Blueprint $table) {
            DB::statement('ALTER TABLE shipments CHANGE port_of_loading departure_port VARCHAR(100) NOT NULL');
            DB::statement('ALTER TABLE shipments CHANGE port_of_discharge arrival_port VARCHAR(100) NOT NULL');
            DB::statement('ALTER TABLE shipments CHANGE actual_departure departure_date DATE NULL');
        });
        
        // Update status enum to match design
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropColumn('status');
        });
        
        Schema::table('shipments', function (Blueprint $table) {
            $table->enum('status', ['preparing', 'in_transit', 'customs', 'delivered', 'delayed'])->default('preparing')->after('tracking_updates');
        });
        
        // Remove fields not in design
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropColumn(['vessel_imo', 'current_latitude', 'current_longitude', 'customs_status', 'last_update']);
        });
        
        // Add indexes for performance
        Schema::table('shipments', function (Blueprint $table) {
            $table->index('tracking_number');
            $table->index('status');
            $table->index('estimated_arrival');
            $table->index(['booking_id', 'status']);
            $table->index(['status', 'estimated_arrival']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            // Remove added columns
            $table->dropColumn(['tracking_number', 'carrier_name', 'tracking_updates']);
            
            // Restore original column names
            DB::statement('ALTER TABLE shipments CHANGE departure_port port_of_loading VARCHAR(100) NOT NULL');
            DB::statement('ALTER TABLE shipments CHANGE arrival_port port_of_discharge VARCHAR(100) NOT NULL');
            DB::statement('ALTER TABLE shipments CHANGE departure_date actual_departure DATE NULL');
            
            // Restore removed columns
            $table->string('vessel_imo', 50)->nullable();
            $table->decimal('current_latitude', 10, 8)->nullable();
            $table->decimal('current_longitude', 11, 8)->nullable();
            $table->enum('customs_status', ['pending', 'submitted', 'approved', 'rejected'])->nullable();
            $table->timestamp('last_update')->useCurrent()->useCurrentOnUpdate();
            
            // Remove indexes
            $table->dropIndex(['tracking_number']);
            $table->dropIndex(['status']);
            $table->dropIndex(['estimated_arrival']);
            $table->dropIndex(['booking_id', 'status']);
            $table->dropIndex(['status', 'estimated_arrival']);
        });
    }
};
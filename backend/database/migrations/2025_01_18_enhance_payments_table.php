<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Add missing fields from design document
            $table->string('payment_reference', 50)->unique()->after('id');
            $table->timestamp('payment_date')->nullable()->after('status');
            $table->text('notes')->nullable()->after('payment_date');
        });
        
        // Update payment_method enum to match design
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('payment_method');
        });
        
        Schema::table('payments', function (Blueprint $table) {
            $table->enum('payment_method', ['bank_transfer', 'mobile_money', 'credit_card', 'cash'])->after('currency');
        });
        
        // Update status enum to match design
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('status');
        });
        
        Schema::table('payments', function (Blueprint $table) {
            $table->enum('status', ['pending', 'completed', 'failed', 'refunded', 'cancelled'])->default('pending')->after('notes');
        });
        
        // Update payment_gateway from enum to string
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('payment_gateway');
        });
        
        Schema::table('payments', function (Blueprint $table) {
            $table->string('payment_gateway', 50)->nullable()->after('payment_method');
            
            // Add proper foreign key for customer_id
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('restrict');
        });
        
        // Add indexes for performance
        Schema::table('payments', function (Blueprint $table) {
            $table->index('payment_reference');
            $table->index('status');
            $table->index('payment_date');
            $table->index(['booking_id', 'status']);
            $table->index(['customer_id', 'status']);
            $table->index(['status', 'payment_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // Remove added columns
            $table->dropColumn(['payment_reference', 'payment_date', 'notes']);
            
            // Remove indexes
            $table->dropIndex(['payment_reference']);
            $table->dropIndex(['status']);
            $table->dropIndex(['payment_date']);
            $table->dropIndex(['booking_id', 'status']);
            $table->dropIndex(['customer_id', 'status']);
            $table->dropIndex(['status', 'payment_date']);
        });
    }
};
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
        Schema::table('quotes', function (Blueprint $table) {
            // Add missing fields from design document
            $table->string('quote_reference', 20)->unique()->after('id');
            $table->json('vehicle_details')->after('route_id');
            $table->json('additional_fees')->nullable()->after('base_price');
            $table->date('valid_until')->after('total_price'); // Use existing column name
            $table->text('notes')->nullable()->after('valid_until');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null')->after('notes');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null')->after('created_by');
            $table->timestamp('approved_at')->nullable()->after('approved_by');
        });
        
        // Handle column renames and status changes in separate calls
        Schema::table('quotes', function (Blueprint $table) {
            DB::statement('ALTER TABLE quotes CHANGE total_price total_amount DECIMAL(10,2) NOT NULL');
        });
        
        Schema::table('quotes', function (Blueprint $table) {
            // Update status enum to match design
            $table->dropColumn('status');
        });
        
        Schema::table('quotes', function (Blueprint $table) {
            $table->enum('status', ['pending', 'approved', 'rejected', 'converted', 'expired'])->default('pending')->after('approved_at');
        });
        
        Schema::table('quotes', function (Blueprint $table) {
            // Remove fields that are now in vehicle_details JSON
            $table->dropColumn(['origin_country', 'origin_city', 'destination_country', 'destination_city']);
            $table->dropColumn(['insurance_price', 'services_price', 'estimated_days']);
            $table->dropColumn(['is_converted', 'expires_at']);
            
            // Add proper foreign key for customer_id
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('restrict');
        });
        
        Schema::table('quotes', function (Blueprint $table) {
            // Add indexes for performance
            $table->index('quote_reference');
            $table->index('status');
            $table->index('valid_until');
            $table->index('created_at');
            $table->index(['customer_id', 'status']);
            $table->index(['status', 'valid_until']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            // Remove added columns
            $table->dropColumn([
                'quote_reference', 'vehicle_details', 'additional_fees', 
                'valid_until', 'notes', 'created_by', 'approved_by', 'approved_at'
            ]);
            
            // Restore original columns
            $table->string('origin_country', 100);
            $table->string('origin_city', 100);
            $table->string('destination_country', 100);
            $table->string('destination_city', 100);
            $table->decimal('insurance_price', 12, 2)->default(0);
            $table->decimal('services_price', 12, 2)->default(0);
            $table->integer('estimated_days')->nullable();
            $table->boolean('is_converted')->default(false);
            $table->timestamp('expires_at')->nullable();
            DB::statement('ALTER TABLE quotes CHANGE total_amount total_price DECIMAL(10,2) NOT NULL');
            
            // Remove indexes
            $table->dropIndex(['quote_reference']);
            $table->dropIndex(['status']);
            $table->dropIndex(['valid_until']);
            $table->dropIndex(['created_at']);
            $table->dropIndex(['customer_id', 'status']);
            $table->dropIndex(['status', 'valid_until']);
        });
    }
};
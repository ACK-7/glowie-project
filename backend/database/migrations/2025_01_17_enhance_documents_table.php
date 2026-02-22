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
        // First, handle column renames
        Schema::table('documents', function (Blueprint $table) {
            DB::statement('ALTER TABLE documents CHANGE file_mime_type mime_type VARCHAR(100) NOT NULL');
            DB::statement('ALTER TABLE documents CHANGE verification_status status VARCHAR(50) NOT NULL');
            DB::statement('ALTER TABLE documents CHANGE verification_notes rejection_reason TEXT NULL');
        });
        
        // Then update document_type enum
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn('document_type');
        });
        
        Schema::table('documents', function (Blueprint $table) {
            $table->enum('document_type', ['passport', 'license', 'invoice', 'insurance', 'customs', 'other'])->after('customer_id');
        });
        
        // Add missing fields from design
        Schema::table('documents', function (Blueprint $table) {
            $table->date('expiry_date')->nullable()->after('rejection_reason');
            
            // Add proper foreign key for customer_id
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            
            // Remove fields not in design
            $table->dropColumn(['ocr_data', 'is_required']);
        });
        
        // Add indexes for performance
        Schema::table('documents', function (Blueprint $table) {
            $table->index('document_type');
            $table->index('status');
            $table->index('expiry_date');
            $table->index(['booking_id', 'document_type']);
            $table->index(['customer_id', 'status']);
            $table->index(['status', 'expiry_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            // Restore original column names
            DB::statement('ALTER TABLE documents CHANGE mime_type file_mime_type VARCHAR(100) NOT NULL');
            DB::statement('ALTER TABLE documents CHANGE status verification_status VARCHAR(50) NOT NULL');
            DB::statement('ALTER TABLE documents CHANGE rejection_reason verification_notes TEXT NULL');
            
            // Remove added columns
            $table->dropColumn(['expiry_date']);
            
            // Restore removed columns
            $table->json('ocr_data')->nullable();
            $table->boolean('is_required')->default(true);
            
            // Remove indexes
            $table->dropIndex(['document_type']);
            $table->dropIndex(['status']);
            $table->dropIndex(['expiry_date']);
            $table->dropIndex(['booking_id', 'document_type']);
            $table->dropIndex(['customer_id', 'status']);
            $table->dropIndex(['status', 'expiry_date']);
        });
    }
};
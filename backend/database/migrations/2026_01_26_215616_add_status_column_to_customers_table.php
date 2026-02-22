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
        Schema::table('customers', function (Blueprint $table) {
            // Add status column with enum type
            $table->enum('status', ['active', 'inactive', 'suspended'])
                  ->default('active')
                  ->after('is_active');
            
            // Add index for better query performance
            $table->index('status');
        });
        
        // Migrate existing data: set status based on is_active
        DB::statement("UPDATE customers SET status = CASE WHEN is_active = 1 THEN 'active' ELSE 'inactive' END");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropColumn('status');
        });
    }
};

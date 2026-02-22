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
        Schema::table('bookings', function (Blueprint $table) {
            // Drop the incorrect foreign key constraint that references users table
            $table->dropForeign('bookings_user_id_foreign');
            
            // Add the correct foreign key constraint that references customers table
            $table->foreign('customer_id')
                  ->references('id')
                  ->on('customers')
                  ->onDelete('cascade')
                  ->name('bookings_customer_id_foreign');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // Drop the correct foreign key
            $table->dropForeign('bookings_customer_id_foreign');
            
            // Restore the old incorrect foreign key (for rollback purposes)
            $table->foreign('customer_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade')
                  ->name('bookings_user_id_foreign');
        });
    }
};

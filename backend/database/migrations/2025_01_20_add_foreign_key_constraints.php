<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * This migration adds foreign key constraints that couldn't be added earlier due to table creation order.
     */
    public function up(): void
    {
        // Add foreign key constraints for quotes table
        if (Schema::hasTable('quotes') && Schema::hasTable('customers')) {
            Schema::table('quotes', function (Blueprint $table) {
                if (!$this->foreignKeyExists('quotes', 'quotes_customer_id_foreign')) {
                    $table->foreign('customer_id')->references('id')->on('customers')->onDelete('restrict');
                }
            });
        }

        // Add foreign key constraints for documents table
        if (Schema::hasTable('documents') && Schema::hasTable('customers')) {
            Schema::table('documents', function (Blueprint $table) {
                if (!$this->foreignKeyExists('documents', 'documents_customer_id_foreign')) {
                    $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
                }
            });
        }

        // Add foreign key constraints for payments table
        if (Schema::hasTable('payments') && Schema::hasTable('customers')) {
            Schema::table('payments', function (Blueprint $table) {
                if (!$this->foreignKeyExists('payments', 'payments_customer_id_foreign')) {
                    $table->foreign('customer_id')->references('id')->on('customers')->onDelete('restrict');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove foreign key constraints - SQLite compatible
        if (config('database.default') !== 'sqlite') {
            Schema::table('quotes', function (Blueprint $table) {
                $table->dropForeign(['customer_id']);
            });

            Schema::table('documents', function (Blueprint $table) {
                $table->dropForeign(['customer_id']);
            });

            Schema::table('payments', function (Blueprint $table) {
                $table->dropForeign(['customer_id']);
            });
        }
    }

    /**
     * Check if a foreign key constraint exists
     */
    private function foreignKeyExists(string $table, string $constraintName): bool
    {
        try {
            $foreignKeys = DB::select("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.TABLE_CONSTRAINTS 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = ? 
                AND CONSTRAINT_TYPE = 'FOREIGN KEY' 
                AND CONSTRAINT_NAME = ?
            ", [$table, $constraintName]);
            
            return count($foreignKeys) > 0;
        } catch (\Exception $e) {
            // If we can't check, assume it doesn't exist
            return false;
        }
    }
};
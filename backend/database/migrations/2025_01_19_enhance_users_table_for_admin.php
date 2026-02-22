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
        // First, add the combined name field and email_verified_at
        Schema::table('users', function (Blueprint $table) {
            $table->string('name', 255)->nullable()->after('id'); // Combined name field
            $table->timestamp('email_verified_at')->nullable()->after('email');
        });
        
        // Update existing users to have combined name (SQLite compatible)
        if (config('database.default') === 'sqlite') {
            // SQLite doesn't support CONCAT, use || operator
            DB::statement("UPDATE users SET name = first_name || ' ' || last_name WHERE name IS NULL OR name = ''");
        } else {
            // MySQL/PostgreSQL support CONCAT
            DB::statement("UPDATE users SET name = CONCAT(first_name, ' ', last_name) WHERE name IS NULL OR name = ''");
        }
        
        // Make name field required after populating it (SQLite compatible)
        if (config('database.default') !== 'sqlite') {
            // Only change column nullability for MySQL (requires Doctrine DBAL)
            Schema::table('users', function (Blueprint $table) {
                $table->string('name', 255)->nullable(false)->change();
                $table->rememberToken()->after('password'); // Add remember_token column
            });
        } else {
            // For SQLite, just add remember_token
            Schema::table('users', function (Blueprint $table) {
                $table->rememberToken()->after('password'); // Add remember_token column
            });
        }
        
        // Drop the separate name columns after combining
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['first_name', 'last_name']);
        });
        
        // Update role enum to include new values (SQLite compatible)
        if (config('database.default') !== 'sqlite') {
            // MySQL supports ALTER ENUM
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('super_admin', 'admin', 'manager', 'operator', 'support') DEFAULT 'operator'");
        }
        // For SQLite, we'll skip this as it doesn't support ENUM modifications
        
        // Add indexes for performance
        Schema::table('users', function (Blueprint $table) {
            // Only add indexes if they don't already exist
            if (!$this->indexExists('users', 'users_role_index')) {
                $table->index('role');
            }
            if (!$this->indexExists('users', 'users_is_active_role_index')) {
                $table->index(['is_active', 'role']);
            }
            if (!$this->indexExists('users', 'users_last_login_at_index')) {
                $table->index('last_login_at');
            }
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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Add back separate name columns
            $table->string('first_name', 100)->after('id');
            $table->string('last_name', 100)->after('first_name');
            
            // Remove combined name field and email_verified_at
            $table->dropColumn(['name', 'email_verified_at', 'remember_token']);
            
            // Remove indexes if they exist
            if ($this->indexExists('users', 'users_role_index')) {
                $table->dropIndex(['role']);
            }
            if ($this->indexExists('users', 'users_is_active_role_index')) {
                $table->dropIndex(['is_active', 'role']);
            }
            if ($this->indexExists('users', 'users_last_login_at_index')) {
                $table->dropIndex(['last_login_at']);
            }
        });
        
        // Revert role enum to original values (SQLite compatible)
        if (config('database.default') !== 'sqlite') {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'manager', 'support') DEFAULT 'support'");
        }
    }
};
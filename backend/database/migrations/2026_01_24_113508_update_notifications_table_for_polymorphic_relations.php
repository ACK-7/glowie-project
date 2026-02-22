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
        Schema::table('notifications', function (Blueprint $table) {
            // Add polymorphic columns for Laravel notifications
            $table->string('notifiable_type')->after('id');
            $table->unsignedBigInteger('notifiable_id')->after('notifiable_type');
            
            // Add data column for storing additional notification data
            $table->json('data')->nullable()->after('message');
            
            // Add channels column for storing notification channels
            $table->json('channels')->nullable()->after('data');
            
            // Remove old columns that are no longer needed
            $table->dropColumn(['customer_id', 'related_booking_id', 'notification_channel', 'sent_at']);
            
            // Add index for polymorphic relationship
            $table->index(['notifiable_type', 'notifiable_id']);
        });
        
        // Update the type column to be a string instead of enum
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropColumn('type');
        });
        
        Schema::table('notifications', function (Blueprint $table) {
            $table->string('type')->after('notifiable_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            // Restore original structure
            $table->dropIndex(['notifiable_type', 'notifiable_id']);
            $table->dropColumn(['notifiable_type', 'notifiable_id', 'data', 'channels', 'type']);
            
            $table->unsignedBigInteger('customer_id')->index()->after('id');
            $table->enum('type', ['booking', 'shipment', 'document', 'payment', 'system'])->after('customer_id');
            $table->unsignedBigInteger('related_booking_id')->nullable()->after('message');
            $table->enum('notification_channel', ['email', 'sms', 'in_app'])->default('in_app')->after('is_read');
            $table->timestamp('sent_at')->nullable()->after('notification_channel');
        });
    }
};
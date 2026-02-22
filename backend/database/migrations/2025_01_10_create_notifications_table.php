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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            // Plain customer_id with index; relation enforced at app level.
            $table->unsignedBigInteger('customer_id')->index();
            $table->enum('type', ['booking', 'shipment', 'document', 'payment', 'system']);
            $table->string('title', 200);
            $table->text('message');
            $table->unsignedBigInteger('related_booking_id')->nullable();
            $table->boolean('is_read')->default(false);
            $table->enum('notification_channel', ['email', 'sms', 'in_app'])->default('in_app');
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};

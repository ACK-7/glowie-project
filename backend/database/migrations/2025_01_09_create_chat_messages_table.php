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
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            // Plain customer_id with index; relation enforced at app level.
            $table->unsignedBigInteger('customer_id')->index();
            $table->text('message_text');
            $table->enum('sender_type', ['user', 'bot', 'support'])->default('user');
            $table->boolean('is_resolved')->default(false);
            $table->string('session_id', 100)->nullable();
            $table->text('ai_response')->nullable();
            $table->decimal('confidence_score', 5, 4)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
    }
};

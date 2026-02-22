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
        if (! Schema::hasTable('bookings')) {
            Schema::create('bookings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
                $table->foreignId('quote_id')->constrained()->onDelete('cascade');
                $table->foreignId('vehicle_id')->constrained()->onDelete('cascade');
                $table->string('reference_number', 50)->unique();
                $table->date('pickup_date');
                $table->date('delivery_date_estimated')->nullable();
                $table->date('delivery_date_actual')->nullable();
                $table->enum('status', ['pending', 'confirmed', 'in_transit', 'delivered', 'cancelled'])->default('pending');
                $table->text('special_instructions')->nullable();
                $table->string('recipient_name', 150);
                $table->string('recipient_phone', 20);
                $table->string('recipient_email');
                $table->string('recipient_country', 100);
                $table->string('recipient_city', 100);
                $table->text('recipient_address');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};

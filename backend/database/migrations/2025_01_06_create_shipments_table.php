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
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->onDelete('cascade');
            $table->string('container_number', 50)->nullable();
            $table->string('vessel_name', 150)->nullable();
            $table->string('vessel_imo', 50)->nullable();
            $table->string('port_of_loading', 150)->nullable();
            $table->string('port_of_discharge', 150)->nullable();
            $table->date('actual_departure')->nullable();
            $table->date('estimated_arrival')->nullable();
            $table->date('actual_arrival')->nullable();
            $table->string('current_location')->nullable();
            $table->decimal('current_latitude', 10, 8)->nullable();
            $table->decimal('current_longitude', 11, 8)->nullable();
            $table->enum('status', ['pending', 'dispatched', 'in_transit', 'customs', 'cleared', 'delivered'])->default('pending');
            $table->enum('customs_status', ['pending', 'submitted', 'approved', 'rejected'])->nullable();
            $table->timestamp('last_update')->useCurrent()->useCurrentOnUpdate();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};

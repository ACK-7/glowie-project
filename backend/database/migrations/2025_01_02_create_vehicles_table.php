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
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vehicle_type_id');
            $table->string('make', 100);
            $table->string('model', 100);
            $table->integer('year');
            $table->string('color', 50)->nullable();
            $table->string('vin', 17)->unique()->nullable();
            $table->string('license_plate', 20)->nullable();
            $table->enum('engine_type', ['petrol', 'diesel', 'hybrid', 'electric'])->default('petrol');
            $table->enum('transmission', ['automatic', 'manual'])->default('automatic');
            $table->decimal('weight', 10, 2)->nullable();
            $table->decimal('height', 10, 2)->nullable();
            $table->decimal('length', 10, 2)->nullable();
            $table->decimal('width', 10, 2)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_running')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};

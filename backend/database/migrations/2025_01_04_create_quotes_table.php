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
        Schema::create('quotes', function (Blueprint $table) {
            $table->id();
            // Use a plain customer_id column so this table can be created
            // before the customers table; we can enforce the relation at
            // the application level or add a separate FK migration later.
            $table->unsignedBigInteger('customer_id')->index();
            $table->foreignId('vehicle_id')->constrained()->onDelete('cascade');
            $table->foreignId('route_id')->constrained()->onDelete('cascade');
            $table->string('origin_country', 100);
            $table->string('origin_city', 100);
            $table->string('destination_country', 100);
            $table->string('destination_city', 100);
            $table->decimal('base_price', 12, 2);
            $table->decimal('insurance_price', 12, 2)->default(0);
            $table->decimal('services_price', 12, 2)->default(0);
            $table->decimal('total_price', 12, 2);
            $table->integer('estimated_days')->nullable();
            $table->string('currency', 3)->default('USD');
            $table->boolean('is_converted')->default(false);
            $table->enum('status', ['pending', 'approved', 'expired', 'rejected'])->default('pending');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quotes');
    }
};

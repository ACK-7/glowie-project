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
        Schema::create('cars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained('car_brands')->onDelete('restrict');
            $table->foreignId('category_id')->constrained('car_categories')->onDelete('restrict');
            $table->string('model');
            $table->string('slug')->unique();
            $table->integer('year');
            $table->decimal('price', 12, 2);
            $table->string('color', 100);
            $table->enum('fuel_type', ['petrol', 'diesel', 'hybrid', 'electric']);
            $table->enum('transmission', ['automatic', 'manual', 'cvt']);
            $table->enum('condition', ['new', 'used', 'certified_pre_owned'])->default('used');
            $table->string('location_country', 100);
            $table->string('location_city', 100);
            $table->text('description')->nullable();
            $table->string('engine_type', 100)->nullable();
            $table->integer('mileage')->nullable();
            $table->enum('drive_type', ['fwd', 'rwd', 'awd', '4wd'])->nullable();
            $table->integer('doors')->nullable();
            $table->integer('seats')->nullable();
            $table->enum('status', ['available', 'sold', 'reserved', 'inactive'])->default('available');
            $table->boolean('is_featured')->default(false);
            $table->timestamp('featured_until')->nullable();
            $table->integer('views_count')->default(0);
            $table->integer('inquiries_count')->default(0);
            $table->timestamps();
            
            // Indexes
            $table->index('brand_id');
            $table->index('category_id');
            $table->index('status');
            $table->index('is_featured');
            $table->index('price');
            $table->index('year');
            $table->index(['status', 'is_featured']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cars');
    }
};

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
        Schema::table('cars', function (Blueprint $table) {
            // Add missing columns from Car model
            $table->string('vin')->nullable()->after('color');
            $table->decimal('length', 8, 2)->nullable()->after('seats');
            $table->decimal('width', 8, 2)->nullable()->after('length');
            $table->decimal('height', 8, 2)->nullable()->after('width');
            $table->decimal('weight', 8, 2)->nullable()->after('height');
            $table->string('currency', 3)->default('USD')->after('price');
            $table->string('dealer_name')->nullable()->after('location_city');
            $table->string('dealer_contact')->nullable()->after('dealer_name');
            $table->integer('estimated_shipping_days_min')->nullable()->after('dealer_contact');
            $table->integer('estimated_shipping_days_max')->nullable()->after('estimated_shipping_days_min');
            $table->decimal('shipping_cost', 10, 2)->nullable()->after('estimated_shipping_days_max');
            $table->boolean('is_running')->default(true)->after('is_featured');
            $table->json('features')->nullable()->after('is_running');
            $table->json('safety_features')->nullable()->after('features');
            $table->text('meta_description')->nullable()->after('description');
            $table->json('tags')->nullable()->after('slug');
            $table->decimal('rating', 3, 2)->nullable()->after('tags');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cars', function (Blueprint $table) {
            $table->dropColumn([
                'vin',
                'length',
                'width',
                'height',
                'weight',
                'currency',
                'dealer_name',
                'dealer_contact',
                'estimated_shipping_days_min',
                'estimated_shipping_days_max',
                'shipping_cost',
                'is_running',
                'features',
                'safety_features',
                'meta_description',
                'tags',
                'rating'
            ]);
        });
    }
};

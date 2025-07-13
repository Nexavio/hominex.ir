<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consultant_id')->constrained()->onDelete('cascade');
            $table->foreignId('property_type_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('property_status', ['for_sale', 'for_rent']);
            $table->decimal('total_price', 15, 0)->nullable();
            $table->decimal('rent_deposit', 15, 0)->nullable();
            $table->decimal('monthly_rent', 15, 0)->nullable();
            $table->integer('land_area')->nullable();
            $table->integer('building_year')->nullable();
            $table->integer('rooms_count')->nullable();
            $table->integer('bathrooms_count')->nullable();
            $table->string('document_type', 50)->nullable();
            $table->integer('total_units')->nullable();
            $table->string('usage_type', 50)->default('residential');
            $table->string('direction', 50)->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('province', 100)->nullable();
            $table->string('city', 100)->nullable();
            $table->text('address')->nullable();
            $table->json('features')->nullable();
            $table->enum('status', ['draft', 'pending', 'approved', 'rejected', 'archived'])->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->integer('views_count')->default(0);
            $table->boolean('is_featured')->default(false);
            $table->timestamp('featured_until')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};

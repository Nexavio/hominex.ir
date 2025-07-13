<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consultants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->onDelete('cascade');
            $table->string('company_name')->nullable();
            $table->string('profile_image', 500)->nullable();
            $table->text('bio')->nullable();
            $table->string('contact_phone', 20)->nullable();
            $table->string('contact_whatsapp', 20)->nullable();
            $table->string('contact_telegram', 50)->nullable();
            $table->string('contact_instagram', 50)->nullable();
            $table->boolean('is_verified')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consultants');
    }
};

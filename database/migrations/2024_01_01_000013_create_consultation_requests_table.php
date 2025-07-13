<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consultation_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('consultant_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('property_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('full_name', 100);
            $table->string('phone', 20);
            $table->text('message')->nullable();
            $table->enum('preferred_contact_method', ['phone', 'whatsapp', 'telegram'])->default('phone');
            $table->string('preferred_contact_time', 100)->nullable();
            $table->enum('status', ['pending', 'contacted', 'in_progress', 'completed', 'cancelled'])->default('pending');
            $table->text('consultant_notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consultation_requests');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comparison_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('comparison_sessions')->onDelete('cascade');
            $table->foreignId('property_id')->constrained()->onDelete('cascade');
            $table->integer('display_order');
            $table->timestamp('added_at')->useCurrent();

            $table->unique(['session_id', 'property_id']);
            $table->index('session_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comparison_items');
    }
};

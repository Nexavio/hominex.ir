<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->enum('level', ['debug', 'info', 'warning', 'error', 'critical']);
            $table->string('action', 100);
            $table->string('module', 50);
            $table->text('description')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('request_method', 10)->nullable();
            $table->string('request_url', 500)->nullable();
            $table->json('request_payload')->nullable();
            $table->integer('response_status')->nullable();
            $table->integer('response_time_ms')->nullable();
            $table->string('error_code', 50)->nullable();
            $table->text('error_message')->nullable();
            $table->text('stack_trace')->nullable();
            $table->string('session_id', 100)->nullable();
            $table->string('correlation_id', 100)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('level');
            $table->index('action');
            $table->index('module');
            $table->index('created_at');
            $table->index('correlation_id');
            $table->index('session_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_logs');
    }
};

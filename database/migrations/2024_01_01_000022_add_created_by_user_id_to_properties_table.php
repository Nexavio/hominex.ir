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
        Schema::table('properties', function (Blueprint $table) {
            // اضافه کردن فیلد created_by_user_id بعد از consultant_id
            $table->foreignId('created_by_user_id')
                  ->nullable()
                  ->after('consultant_id')
                  ->constrained('users')
                  ->onDelete('set null')
                  ->comment('کاربری که این آگهی را ثبت کرده است');

            // اضافه کردن index برای جستجوی سریع‌تر
            $table->index(['created_by_user_id', 'status']);
            $table->index(['consultant_id', 'created_by_user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            // حذف indexes
            $table->dropIndex(['created_by_user_id', 'status']);
            $table->dropIndex(['consultant_id', 'created_by_user_id']);

            // حذف foreign key و column
            $table->dropConstrainedForeignId('created_by_user_id');
        });
    }
};

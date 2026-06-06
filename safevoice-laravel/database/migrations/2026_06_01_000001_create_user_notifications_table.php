<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('user_notifications')) {
            Schema::create('user_notifications', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->index();
                $table->string('type', 60)->default('general');
                $table->string('title', 255);
                $table->text('message');
                $table->string('complaint_id', 30)->nullable()->index();
                $table->string('action_url', 500)->nullable();
                $table->string('icon', 10)->default('🔔');
                $table->boolean('is_read')->default(false)->index();
                $table->timestamp('created_at')->useCurrent();

                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->index(['user_id', 'is_read']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('user_notifications');
    }
};
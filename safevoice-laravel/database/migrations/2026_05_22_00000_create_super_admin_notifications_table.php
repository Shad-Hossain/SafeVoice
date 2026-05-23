<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Super Admin Notifications Table
 * যখন সব PI একটা case reject করে, super admin এর জন্য in-app notification তৈরি হবে।
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('super_admin_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('type', 80)->default('all_pi_rejected');
            $table->string('title', 200);
            $table->text('message');
            $table->string('complaint_id', 20)->nullable()->index();
            $table->string('action_url', 500)->nullable();
            $table->string('icon', 10)->default('⚠️');
            $table->boolean('is_read')->default(false);
            $table->datetime('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('super_admin_notifications');
    }
};
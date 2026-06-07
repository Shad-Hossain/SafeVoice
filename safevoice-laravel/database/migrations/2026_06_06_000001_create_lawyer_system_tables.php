<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── 1. lawyers ─────────────────────────────────────────────────
        Schema::create('lawyers', function (Blueprint $table) {
            $table->id();
            $table->string('lawyer_code', 20)->unique();         // LAW001, LAW002 ...
            $table->string('full_name', 100);
            $table->string('email', 150)->unique();
            $table->string('email_hash', 64)->unique();          // sha256 — login lookup
            $table->string('phone', 20)->unique();
            $table->string('password_hash', 255);
            $table->string('bar_council_id', 50)->unique();      // OCR থেকে auto-extract, edit নয়
            $table->string('bar_council_photo', 500)->nullable(); // uploaded card image
            $table->string('profile_photo', 500)->nullable();
            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->json('specializations')->nullable();          // ['family','criminal','property']
            $table->integer('experience_years')->default(0);
            $table->decimal('min_fee', 10, 2)->default(500.00);  // ৳ minimum consultation fee
            $table->text('bio')->nullable();
            $table->enum('status', ['Pending', 'Active', 'Suspended', 'Banned'])->default('Pending');
            $table->boolean('is_available')->default(true);       // on/off from dashboard
            $table->integer('total_cases')->default(0);
            $table->integer('completed_cases')->default(0);
            $table->decimal('rating', 3, 2)->default(0.00);
            $table->integer('rating_count')->default(0);
            $table->datetime('joined_at')->useCurrent();
            $table->datetime('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        // ── 2. legal_requests (user legal help request) ────────────────
        Schema::create('legal_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_id', 20)->unique();           // LR-20260606-0001
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('user_name', 100)->nullable();
            $table->string('user_phone', 20)->nullable();
            $table->string('issue_type', 100);                    // harassment, labor, fraud ...
            $table->text('description');
            $table->string('location', 255)->nullable();
            $table->boolean('is_urgent')->default(false);
            $table->decimal('budget_max', 10, 2)->nullable();     // user এর maximum budget
            $table->enum('status', [
                'open',          // সব lawyer কে notification গেছে
                'bidding',       // কিছু lawyer bid করেছে, user choose করেনি
                'accepted',      // user একজন lawyer বেছেছে
                'in_progress',   // কাজ চলছে
                'completed',     // শেষ
                'cancelled',     // বাতিল
            ])->default('open');
            $table->unsignedBigInteger('assigned_lawyer_id')->nullable();
            $table->datetime('accepted_at')->nullable();
            $table->datetime('completed_at')->nullable();
            $table->datetime('created_at')->useCurrent();
            $table->datetime('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('assigned_lawyer_id')->references('id')->on('lawyers')->nullOnDelete();
        });

        // ── 3. lawyer_bids (Pathao-style offer) ────────────────────────
        Schema::create('lawyer_bids', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('legal_request_id');
            $table->unsignedBigInteger('lawyer_id');
            $table->decimal('proposed_fee', 10, 2);               // lawyer এর quoted fee ৳
            $table->text('cover_note')->nullable();                // lawyer এর brief intro/offer
            $table->integer('estimated_days')->nullable();         // কত দিনে শেষ করবে
            $table->enum('status', [
                'pending',    // user এখনো দেখেনি
                'seen',       // user দেখেছে
                'accepted',   // user accept করেছে
                'rejected',   // user reject করেছে (অন্য lawyer বেছেছে)
            ])->default('pending');
            $table->datetime('bid_at')->useCurrent();
            $table->datetime('responded_at')->nullable();

            $table->unique(['legal_request_id', 'lawyer_id'], 'unique_bid');
            $table->foreign('legal_request_id')->references('id')->on('legal_requests')->cascadeOnDelete();
            $table->foreign('lawyer_id')->references('id')->on('lawyers')->cascadeOnDelete();
        });

        // ── 4. lawyer_notifications (FCM-style push records) ──────────
        Schema::create('lawyer_notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lawyer_id');
            $table->string('type', 50);                           // new_request, bid_accepted, bid_rejected
            $table->string('title', 200);
            $table->text('body')->nullable();
            $table->json('data')->nullable();                     // {request_id, user_name, ...}
            $table->boolean('is_read')->default(false);
            $table->datetime('created_at')->useCurrent();

            $table->foreign('lawyer_id')->references('id')->on('lawyers')->cascadeOnDelete();
            $table->index(['lawyer_id', 'is_read']);
        });

        // ── 5. lawyer_fcm_tokens ────────────────────────────────────────
        Schema::create('lawyer_fcm_tokens', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lawyer_id');
            $table->text('token');
            $table->datetime('created_at')->useCurrent();

            $table->foreign('lawyer_id')->references('id')->on('lawyers')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lawyer_fcm_tokens');
        Schema::dropIfExists('lawyer_notifications');
        Schema::dropIfExists('lawyer_bids');
        Schema::dropIfExists('legal_requests');
        Schema::dropIfExists('lawyers');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // ── 1. lawyers এ total_earned যোগ করো ────────────────────────
        Schema::table('lawyers', function (Blueprint $table) {
            $table->decimal('total_earned', 12, 2)->default(0.00)->after('completed_cases');
            // commission platform কে দেওয়ার total record
            $table->decimal('total_commission_paid', 12, 2)->default(0.00)->after('total_earned');
        });

        // ── 2. legal_requests এ নতুন status values যোগ করো ──────────
        // MySQL এ ENUM alter করতে হয় — Laravel migration এ DB::statement দিয়ে
        // SQLite তে ENUM = varchar, তাই এটা automatically কাজ করে
        try {
            DB::statement("
                ALTER TABLE legal_requests
                MODIFY COLUMN status ENUM(
                    'open',
                    'bidding',
                    'accepted',
                    'in_progress',
                    'resolved_pending_payment',
                    'payment_disputed',
                    'completed',
                    'expired',
                    'cancelled',
                    'exhausted'
                ) NOT NULL DEFAULT 'open'
            ");
        } catch (\Exception $e) {
            // SQLite তে ENUM alter support নেই — skip (VARCHAR হিসেবে কাজ করবে)
        }

        // ── 3. case_payments table ─────────────────────────────────────
        Schema::create('case_payments', function (Blueprint $table) {
            $table->id();

            // Unique reference code — PAY-20260609-0001
            $table->string('payment_code', 25)->unique();

            $table->unsignedBigInteger('legal_request_id');
            $table->unsignedBigInteger('lawyer_id');
            $table->unsignedBigInteger('user_id')->nullable();

            // Fee breakdown
            $table->decimal('gross_amount', 10, 2);          // lawyer এর bid fee ৳
            $table->decimal('commission', 10, 2)->default(0); // 2% platform cut
            $table->decimal('net_amount', 10, 2);            // lawyer actually পাবে = gross - commission

            // Payment lifecycle
            $table->enum('status', [
                'pending',    // lawyer resolved করেছে, user এখনো pay করেনি
                'claimed',    // user বলেছে pay করেছি, lawyer confirm করেনি
                'confirmed',  // lawyer confirmed → earnings updated
                'disputed',   // lawyer said didn't receive → user warned
                'overdue',    // 3 দিন পার, user পে করেনি
            ])->default('pending');

            // Deadlines
            $table->datetime('payment_deadline');             // user কে pay করতে হবে এর মধ্যে (3 days)
            $table->datetime('paid_claimed_at')->nullable();  // user যখন "paid" বলেছে
            $table->datetime('claim_deadline')->nullable();   // lawyer confirm করতে হবে (48hr after claim)
            $table->datetime('confirmed_at')->nullable();
            $table->datetime('disputed_at')->nullable();

            $table->datetime('created_at')->useCurrent();
            $table->datetime('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->foreign('legal_request_id')->references('id')->on('legal_requests')->cascadeOnDelete();
            $table->foreign('lawyer_id')->references('id')->on('lawyers')->cascadeOnDelete();
            $table->index(['user_id', 'status']);
            $table->index(['lawyer_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('case_payments');

        Schema::table('lawyers', function (Blueprint $table) {
            $table->dropColumn(['total_earned', 'total_commission_paid']);
        });

        try {
            DB::statement("
                ALTER TABLE legal_requests
                MODIFY COLUMN status ENUM(
                    'open','bidding','accepted','in_progress',
                    'completed','expired','cancelled','exhausted'
                ) NOT NULL DEFAULT 'open'
            ");
        } catch (\Exception $e) {}
    }
};
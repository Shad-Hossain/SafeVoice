<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MIGRATION: PI Case Assignment Email Tracking
 *
 * এই migration এ যা add হবে:
 * 1. complaints table: pi_assignment_token, pi_token_expires_at, pi_email_queue (JSON)
 * 2. pi_case_assignments table: কোন PI কে mail করা হয়েছে, কে accept/reject করেছে
 */
return new class extends Migration
{
    public function up(): void
    {
        // complaints table এ নতুন columns
        Schema::table('complaints', function (Blueprint $table) {
            // signed token — mail এর accept/reject link এ থাকবে
            if (!Schema::hasColumn('complaints', 'pi_assignment_token')) {
                $table->string('pi_assignment_token', 64)->nullable()->after('pi_assigned_at');
            }
            // token কতক্ষণ valid থাকবে
            if (!Schema::hasColumn('complaints', 'pi_token_expires_at')) {
                $table->datetime('pi_token_expires_at')->nullable()->after('pi_assignment_token');
            }
            // JSON array: কোন PI দের already mail করা হয়েছে (reject করেছে বা skip)
            if (!Schema::hasColumn('complaints', 'pi_email_queue')) {
                $table->json('pi_email_queue')->nullable()->after('pi_token_expires_at');
                // format: [{"pi_id": 1, "sent_at": "...", "action": "rejected", "acted_at": "..."}]
            }
            // currently কোন PI এর কাছে mail আছে (pending)
            if (!Schema::hasColumn('complaints', 'current_pi_email_id')) {
                $table->unsignedBigInteger('current_pi_email_id')->nullable()->after('pi_email_queue');
            }
        });

        // pi_case_assignments: detailed audit log
        Schema::create('pi_case_assignments', function (Blueprint $table) {
            $table->id();
            $table->string('complaint_id');
            $table->unsignedBigInteger('pi_id');
            $table->string('token', 64)->unique(); // unique signed token
            $table->datetime('token_expires_at');
            $table->enum('status', ['pending', 'accepted', 'rejected', 'expired'])->default('pending');
            $table->datetime('mailed_at')->nullable();
            $table->datetime('acted_at')->nullable();  // accept/reject করার সময়
            $table->string('action_ip', 45)->nullable(); // security log
            $table->timestamps();

            $table->index('token');
            $table->index(['complaint_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('complaints', function (Blueprint $table) {
            $cols = ['pi_assignment_token','pi_token_expires_at','pi_email_queue','current_pi_email_id'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('complaints', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::dropIfExists('pi_case_assignments');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateEvidenceRequestsStatusFields extends Migration
{
   public function up(): void
{
    Schema::table('evidence_requests', function (Blueprint $table) {
        if (!Schema::hasColumn('evidence_requests', 'status')) {
            $table->enum('status', [
                'pending', 'submitted', 'rejected', 'expired', 'pi_assigned'
            ])->default('pending');
        }
        if (!Schema::hasColumn('evidence_requests', 'submitted_at')) {
            $table->timestamp('submitted_at')->nullable();
        }
        if (!Schema::hasColumn('evidence_requests', 'rejected_at')) {
            $table->timestamp('rejected_at')->nullable();
        }
        if (!Schema::hasColumn('evidence_requests', 'expired_at')) {
            $table->timestamp('expired_at')->nullable();
        }
        if (!Schema::hasColumn('evidence_requests', 'pi_assigned_at')) {
            $table->timestamp('pi_assigned_at')->nullable();
        }
    });
}

    public function down(): void
    {
        Schema::table('evidence_requests', function (Blueprint $table) {
            $table->dropColumn([
                'status',
                'submitted_at',
                'rejected_at',
                'expired_at',
                'pi_assigned_at'
            ]);
        });
    }
}
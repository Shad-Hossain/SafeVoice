<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * pi_payments table এ refund tracking columns যোগ করো
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pi_payments', function (Blueprint $table) {
            $table->string('refund_status', 20)->nullable()->after('status');
            // 'processed' = admin manually sent the refund
            $table->datetime('refund_processed_at')->nullable()->after('refund_status');
        });
    }

    public function down(): void
    {
        Schema::table('pi_payments', function (Blueprint $table) {
            $table->dropColumn(['refund_status', 'refund_processed_at']);
        });
    }
};
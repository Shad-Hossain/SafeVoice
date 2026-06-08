<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('legal_requests', function (Blueprint $table) {
            $table->datetime('deadline')->nullable()->after('budget_max');
            $table->boolean('is_instant')->default(false)->after('deadline');
            $table->boolean('deadline_notified')->default(false)->after('is_instant');
        });

        Schema::table('lawyer_bids', function (Blueprint $table) {
            $table->decimal('platform_commission', 10, 2)->nullable()->after('proposed_fee');
        });
    }

    public function down(): void
    {
        Schema::table('legal_requests', function (Blueprint $table) {
            $table->dropColumn(['deadline', 'is_instant', 'deadline_notified']);
        });
        Schema::table('lawyer_bids', function (Blueprint $table) {
            $table->dropColumn('platform_commission');
        });
    }
};
